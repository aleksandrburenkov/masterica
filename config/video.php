<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Диск и каталоги видеоотзывов
    |--------------------------------------------------------------------------
    |
    | Загруженные видео хранятся на приватном диске (по умолчанию "local"),
    | а не в публичной папке: доступ к файлу выдаётся только через серверный
    | маршрут с проверкой прав. Каталоги разделены по назначению, а готовые
    | видео и постеры дополнительно разносятся по годам и месяцам, чтобы в
    | одной папке не скапливались тысячи файлов.
    |
    */

    'disk' => env('VIDEO_DISK', 'local'),

    'directory' => 'reviews/videos',

    'poster_directory' => 'reviews/posters',

    // Загруженные видео лежат здесь до модерации. После одобрения файл
    // переезжает в каталог видео; при отклонении — удаляется.
    'incoming_directory' => 'reviews/incoming',

    'quarantine_directory' => 'reviews/quarantine',

    /*
    |--------------------------------------------------------------------------
    | Ограничения загрузки
    |--------------------------------------------------------------------------
    |
    | Размер указан в килобайтах (правило Laravel "max" для файлов), длительность
    | — в секундах. Длительность проверяется сервером, если доступен ffprobe.
    |
    */

    'max_size_kb' => (int) env('VIDEO_MAX_SIZE_KB', 204800),

    'max_duration_seconds' => (int) env('VIDEO_MAX_DURATION', 600),

    'allowed_extensions' => ['mp4', 'mov', 'm4v', 'webm', 'mkv', 'avi', 'ogv'],

    'allowed_mime_types' => [
        'video/mp4',
        'video/quicktime',
        'video/x-m4v',
        'video/webm',
        'video/x-matroska',
        'video/x-msvideo',
        'video/ogg',
        'video/mpeg',
    ],

    /*
    |--------------------------------------------------------------------------
    | Обработка (сжатие и постеры)
    |--------------------------------------------------------------------------
    |
    | Оптимизация запускается в момент публикации (модерации) отзыва и только
    | для действительно больших файлов — небольшой поток видео не требует
    | фоновой очереди и воркера. Если ffmpeg недоступен, файл сохраняется без
    | изменений, а сценарий остаётся рабочим.
    |
    */

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

    'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),

    'process_timeout' => (int) env('VIDEO_PROCESS_TIMEOUT', 900),

    'compression' => [
        'enabled' => filter_var(env('VIDEO_COMPRESSION', true), FILTER_VALIDATE_BOOL),

        // Сжимаем только видео крупнее этого порога (КБ). Мелкие файлы
        // публикуются как есть, чтобы не тратить время на перекодирование.
        'trigger_size_kb' => (int) env('VIDEO_COMPRESSION_TRIGGER_KB', 25600),

        'max_width' => (int) env('VIDEO_MAX_WIDTH', 1280),
        'crf' => (int) env('VIDEO_CRF', 28),
        'preset' => env('VIDEO_PRESET', 'veryfast'),
        'audio_bitrate' => env('VIDEO_AUDIO_BITRATE', '96k'),
    ],

    'poster' => [
        'enabled' => filter_var(env('VIDEO_POSTER', true), FILTER_VALIDATE_BOOL),
        'width' => (int) env('VIDEO_POSTER_WIDTH', 720),
        'at_seconds' => (int) env('VIDEO_POSTER_AT', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Проверка на вредоносные вложения
    |--------------------------------------------------------------------------
    |
    | Сначала файл проверяется антивирусом ClamAV (если он установлен и доступен),
    | затем — встроенной эвристикой (сигнатуры контейнера, поиск исполняемых
    | файлов, скриптов и полиглотов). Если ClamAV недоступен, используется
    | только эвристика; результат проверки сохраняется в базе.
    |
    */

    'scan' => [
        'clamav' => [
            'enabled' => filter_var(env('VIDEO_CLAMAV_ENABLED', true), FILTER_VALIDATE_BOOL),
            'binary' => env('CLAMSCAN_PATH', 'clamscan'),
            'daemon_binary' => env('CLAMDSCAN_PATH', 'clamdscan'),
            'timeout' => (int) env('VIDEO_CLAMAV_TIMEOUT', 120),
        ],

        'heuristic' => [
            'enabled' => true,
            'head_bytes' => (int) env('VIDEO_SCAN_HEAD_BYTES', 4 * 1024 * 1024),
            'tail_bytes' => (int) env('VIDEO_SCAN_TAIL_BYTES', 1024 * 1024),
        ],
    ],

];
