<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('Корни');
    }

    #[DataProvider('staticPages')]
    public function test_static_pages_load(string $route, array $params = []): void
    {
        $this->get(route($route, $params))->assertOk();
    }

    public static function staticPages(): array
    {
        return [
            'about' => ['about'],
            'how-classes-work' => ['how-classes-work'],
            'contacts' => ['contacts'],
            'masterclasses' => ['masterclasses.index'],
            'bryansk' => ['masterclasses.bryansk'],
            'outreach' => ['outreach.index'],
            'shop' => ['shop.index'],
            'courses' => ['courses.index'],
            'reviews' => ['reviews.index'],
            'guides' => ['guides.index'],
            'privacy' => ['legal.privacy'],
            'consent' => ['legal.consent'],
            'rules' => ['legal.rules'],
        ];
    }

    public function test_catalogue_detail_pages_load(): void
    {
        $this->get(route('masterclasses.show', 'basket'))->assertOk();
        $this->get(route('shop.show', 'basket-paper-vine'))->assertOk();
        $this->get(route('courses.show', 'paper-vine-basket-course'))->assertOk();
        $this->get(route('guides.show', 'paper-vine-guide'))->assertOk();
    }

    public function test_sitemap_and_robots(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap');
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound();
    }

    public function test_account_requires_authentication(): void
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
    }

    public function test_private_pages_are_not_publicly_cacheable_and_not_indexed(): void
    {
        $user = User::where('email', 'demo@korni.local')->firstOrFail();

        $response = $this->actingAs($user)->get(route('account.dashboard'));

        $response->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);

        $cacheControl = (string) $response->headers->get('cache-control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('private', $cacheControl);
    }
}
