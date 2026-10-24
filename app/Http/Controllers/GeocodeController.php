<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Адрес площадки -> координаты для калькулятора удалённости.
 *
 * Сначала используется Яндекс.Геокодер (серверный ключ, не попадает в JS),
 * затем OpenStreetMap/Nominatim. Результат кэшируется на сутки, чтобы не
 * расходовать лимиты геокодеров при повторных запросах.
 */
class GeocodeController extends Controller
{
    private const CACHE_TTL_SECONDS = 86400;

    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        $query = trim($validated['q']);
        $cacheKey = 'geocode.'.md5(mb_strtolower($query));
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return response()->json($cached);
        }

        $coords = $this->geocodeYandex($query) ?? $this->geocodeOpenStreetMap($query);

        if ($coords === null) {
            return response()->json(['message' => 'Адрес не найден'], 404);
        }

        Cache::put($cacheKey, $coords, self::CACHE_TTL_SECONDS);

        return response()->json($coords);
    }

    /**
     * @return array{lat:float,lng:float}|null
     */
    private function geocodeYandex(string $query): ?array
    {
        $key = config('services.yandex_geocoder.key');

        if (blank($key)) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://geocode-maps.yandex.ru/1.x/', [
                    'apikey' => $key,
                    'format' => 'json',
                    'results' => 1,
                    'lang' => 'ru_RU',
                    'geocode' => $query,
                ]);
        } catch (Throwable $exception) {
            Log::warning('Yandex geocoder request failed', ['error' => $exception->getMessage()]);

            return null;
        }

        if (! $response->ok()) {
            Log::warning('Yandex geocoder rejected the request', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 300),
            ]);

            return null;
        }

        /* Point.pos приходит в порядке «долгота широта». */
        $position = data_get(
            $response->json(),
            'response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos',
        );

        if (! is_string($position)) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($position));

        if (! is_array($parts) || count($parts) !== 2) {
            return null;
        }

        return $this->makeCoordinates((float) $parts[1], (float) $parts[0]);
    }

    /**
     * @return array{lat:float,lng:float}|null
     */
    private function geocodeOpenStreetMap(string $query): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Masterica-Geocoder/1.0 (+'.config('app.url').')',
            ])
                ->acceptJson()
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'addressdetails' => 0,
                    'accept-language' => 'ru',
                    'q' => $query,
                ]);
        } catch (Throwable $exception) {
            Log::warning('OSM geocoder request failed', ['error' => $exception->getMessage()]);

            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $row = $response->json('0');

        if (! is_array($row)) {
            return null;
        }

        return $this->makeCoordinates((float) ($row['lat'] ?? 0), (float) ($row['lon'] ?? 0));
    }

    /**
     * @return array{lat:float,lng:float}|null
     */
    private function makeCoordinates(float $lat, float $lng): ?array
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        if ($lat === 0.0 && $lng === 0.0) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }
}
