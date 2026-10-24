<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_geocode_uses_yandex_geocoder_key_and_parses_longitude_latitude(): void
    {
        config(['services.yandex_geocoder.key' => 'test-geocoder-key']);

        Http::fake([
            'geocode-maps.yandex.ru/*' => Http::response([
                'response' => [
                    'GeoObjectCollection' => [
                        'featureMember' => [
                            [
                                'GeoObject' => [
                                    'Point' => ['pos' => '34.1065866 53.3709842'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->getJson(route('geocode', ['q' => 'Сельцо']))
            ->assertOk()
            ->assertJson([
                'lat' => 53.3709842,
                'lng' => 34.1065866,
            ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'geocode-maps.yandex.ru')
                && str_contains($request->url(), 'apikey=test-geocoder-key');
        });
    }

    public function test_geocode_falls_back_to_openstreetmap_when_yandex_rejects_request(): void
    {
        config(['services.yandex_geocoder.key' => 'test-geocoder-key']);

        Http::fake([
            'geocode-maps.yandex.ru/*' => Http::response(['message' => 'Forbidden'], 403),
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.3709842', 'lon' => '34.1065866'],
            ]),
        ]);

        $this->getJson(route('geocode', ['q' => 'Сельцо, Брянская область']))
            ->assertOk()
            ->assertJson([
                'lat' => 53.3709842,
                'lng' => 34.1065866,
            ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'nominatim.openstreetmap.org');
        });
    }

    public function test_geocode_returns_not_found_when_both_providers_fail(): void
    {
        config(['services.yandex_geocoder.key' => 'test-geocoder-key']);

        Http::fake([
            'geocode-maps.yandex.ru/*' => Http::response([], 500),
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $this->getJson(route('geocode', ['q' => 'Несуществующий адрес 12345']))
            ->assertNotFound()
            ->assertJson(['message' => 'Адрес не найден']);
    }

    public function test_geocode_requires_minimal_query_length(): void
    {
        $this->getJson(route('geocode', ['q' => 'ab']))
            ->assertStatus(422);
    }

    public function test_geocode_skips_yandex_without_key(): void
    {
        config(['services.yandex_geocoder.key' => null]);

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '53.3709842', 'lon' => '34.1065866'],
            ]),
        ]);

        $this->getJson(route('geocode', ['q' => 'Сельцо']))
            ->assertOk();

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'geocode-maps.yandex.ru');
        });
    }

    public function test_geocode_caches_repeated_requests(): void
    {
        config(['services.yandex_geocoder.key' => 'test-geocoder-key']);

        Http::fake([
            'geocode-maps.yandex.ru/*' => Http::response([
                'response' => [
                    'GeoObjectCollection' => [
                        'featureMember' => [
                            [
                                'GeoObject' => [
                                    'Point' => ['pos' => '34.1065866 53.3709842'],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->getJson(route('geocode', ['q' => 'Сельцо']))->assertOk();
        $this->getJson(route('geocode', ['q' => 'Сельцо']))->assertOk();

        Http::assertSentCount(1);
    }
}
