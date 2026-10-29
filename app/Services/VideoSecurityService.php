<?php

namespace App\Services;

use App\Enums\VideoScanStatus;
use App\Support\VideoScanResult;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Проверка загруженных видео на вирусы, трояны и другие вредоносные вложения.
 *
 * Сначала файл проверяется антивирусом ClamAV (если он установлен), затем —
 * встроенной эвристикой: проверка сигнатуры видеоконтейнера, поиск внедрённых
 * исполняемых файлов, скриптов и полиглотов. Если ClamAV недоступен,
 * используется только эвристика, а результат проверки сохраняется в базе.
 */
class VideoSecurityService
{
    /** @var array<string, bool> */
    private static array $availability = [];

    public function scan(string $absolutePath, ?string $mime = null, ?string $originalName = null): VideoScanResult
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            return new VideoScanResult(VideoScanStatus::Error, 'filesystem', 'Файл недоступен для проверки.');
        }

        $container = $this->verifyContainer($absolutePath);

        if (! $container['ok']) {
            return new VideoScanResult(VideoScanStatus::Suspicious, 'signature', $container['reason'] ?? 'Неизвестный формат файла.');
        }

        $clamav = $this->scanWithClamAv($absolutePath);

        if ($clamav !== null && ! $clamav->isPlayable()) {
            return $clamav;
        }

        $heuristic = $this->scanHeuristically($absolutePath);

        if (! $heuristic->isClean()) {
            return $heuristic;
        }

        return $clamav ?? $heuristic;
    }

    /**
     * Проверяет, что первые байты файла соответствуют известному видеоконтейнеру.
     *
     * @return array{ok: bool, reason?: string, format?: string}
     */
    private function verifyContainer(string $absolutePath): array
    {
        $head = $this->readBytes($absolutePath, 0, 16);

        if (strlen($head) < 12) {
            return ['ok' => false, 'reason' => 'Файл повреждён или слишком мал.'];
        }

        if (substr($head, 4, 4) === 'ftyp') {
            return ['ok' => true, 'format' => 'iso-base-media'];
        }

        if (substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'AVI ') {
            return ['ok' => true, 'format' => 'avi'];
        }

        if (substr($head, 0, 4) === "\x1A\x45\xDF\xA3") {
            return ['ok' => true, 'format' => 'matroska'];
        }

        if (substr($head, 0, 4) === 'OggS') {
            return ['ok' => true, 'format' => 'ogg'];
        }

        if (substr($head, 0, 4) === "FLV\x01") {
            return ['ok' => true, 'format' => 'flv'];
        }

        if (substr($head, 0, 3) === "\x00\x00\x01" || $head[0] === "\x47") {
            return ['ok' => true, 'format' => 'mpeg'];
        }

        return ['ok' => false, 'reason' => 'Сигнатура файла не соответствует поддерживаемому видеоформату.'];
    }

    /**
     * Полная проверка файла через ClamAV. Возвращает null, если ClamAV
     * недоступен или завершился с ошибкой (тогда используется эвристика).
     */
    private function scanWithClamAv(string $absolutePath): ?VideoScanResult
    {
        $config = (array) config('video.scan.clamav', []);

        if (! ($config['enabled'] ?? false)) {
            return null;
        }

        $candidates = [
            ['binary' => (string) ($config['daemon_binary'] ?? 'clamdscan'), 'args' => ['--fdpass', '--no-summary']],
            ['binary' => (string) ($config['binary'] ?? 'clamscan'), 'args' => ['--no-summary']],
        ];

        $timeout = (int) ($config['timeout'] ?? 120);

        foreach ($candidates as $candidate) {
            $binary = trim($candidate['binary']);

            if ($binary === '' || ! $this->binaryAvailable($binary)) {
                continue;
            }

            try {
                $process = new Process(array_merge([$binary], $candidate['args'], [$absolutePath]));
                $process->setTimeout($timeout);
                $process->run();
            } catch (Throwable $exception) {
                continue;
            }

            $output = trim($process->getOutput()."\n".$process->getErrorOutput());

            if (stripos($output, 'FOUND') !== false) {
                return new VideoScanResult(VideoScanStatus::Infected, 'clamav', $output);
            }

            if ($process->getExitCode() === 0) {
                return VideoScanResult::clean('clamav', 'ClamAV: OK');
            }
        }

        return null;
    }

    /**
     * Резервная эвристическая проверка: сигнатуры внедрённых исполняемых файлов,
     * скриптов, полиглотов и подозрительных ASCII-маркеров.
     */
    private function scanHeuristically(string $absolutePath): VideoScanResult
    {
        $config = (array) config('video.scan.heuristic', []);
        $headBytes = (int) ($config['head_bytes'] ?? 4 * 1024 * 1024);
        $tailBytes = (int) ($config['tail_bytes'] ?? 1024 * 1024);

        $size = filesize($absolutePath) ?: 0;
        $head = $this->readBytes($absolutePath, 0, min($headBytes, $size));
        $tail = $size > $headBytes ? $this->readBytes($absolutePath, max(0, $size - $tailBytes), $tailBytes) : '';

        $sample = $head.$tail;
        $threats = [];
        $suspicions = [];

        if ($this->hasPortableExecutable($head) || str_contains($sample, "\x7FELF")) {
            $threats[] = 'внедрённый исполняемый файл';
        }

        // Только длинные ASCII-маркеры. Короткие строки (например, "<%", "<?=")
        // случайно встречаются в двоичных данных видео и дают ложные срабатывания,
        // поэтому в списке остаются только устойчивые последовательности.
        $scriptMarkers = [
            '<?php', '<script', '</script', 'javascript:', 'vbscript:',
            'WScript.Shell', 'ActiveXObject', 'CreateObject(', 'base64_decode(',
            'eval(base64', 'eval($_', 'shell_exec', 'system($_', 'passthru(',
        ];

        foreach ($scriptMarkers as $marker) {
            if (str_contains($sample, $marker)) {
                $threats[] = 'скрипт ('.$marker.')';
            }
        }

        if (str_contains($sample, "PK\x03\x04")) {
            $suspicions[] = 'архив, встроенный в видео (полиглот)';
        }

        if (str_starts_with($sample, '#!')) {
            $suspicions[] = 'исполняемый сценарий (shebang)';
        }

        if ($threats !== []) {
            return new VideoScanResult(VideoScanStatus::Infected, 'heuristic', 'Найдено: '.implode('; ', array_unique($threats)).'.');
        }

        if ($suspicions !== []) {
            return new VideoScanResult(VideoScanStatus::Suspicious, 'heuristic', 'Найдено: '.implode('; ', array_unique($suspicions)).'.');
        }

        return VideoScanResult::clean('heuristic', 'Эвристика: подозрительных вложений не найдено.');
    }

    /** Проверка корректности PE-заголовка Windows (а не случайного «MZ»). */
    private function hasPortableExecutable(string $data): bool
    {
        if (strlen($data) < 0x40 || substr($data, 0, 2) !== 'MZ') {
            return false;
        }

        $offset = unpack('V', substr($data, 0x3C, 4))[1] ?? 0;

        if ($offset <= 0 || $offset + 4 > strlen($data)) {
            return false;
        }

        return substr($data, $offset, 4) === "PE\x00\x00";
    }

    private function readBytes(string $path, int $offset, int $length): string
    {
        if ($length <= 0) {
            return '';
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            if ($offset > 0 && fseek($handle, $offset) !== 0) {
                return '';
            }

            $buffer = '';
            $remaining = $length;

            while ($remaining > 0 && ! feof($handle)) {
                $chunk = fread($handle, min($remaining, 1024 * 1024));

                if ($chunk === false || $chunk === '') {
                    break;
                }

                $buffer .= $chunk;
                $remaining -= strlen($chunk);
            }

            return $buffer;
        } finally {
            fclose($handle);
        }
    }

    private function binaryAvailable(string $binary): bool
    {
        if (array_key_exists($binary, self::$availability)) {
            return self::$availability[$binary];
        }

        try {
            $process = new Process([$binary, '--version']);
            $process->setTimeout(10);
            $process->run();
            $available = $process->isSuccessful();
        } catch (Throwable) {
            $available = false;
        }

        return self::$availability[$binary] = $available;
    }
}
