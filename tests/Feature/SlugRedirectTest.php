<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\ContentPage;
use App\Models\Course;
use App\Models\LegalDocument;
use App\Models\Masterclass;
use App\Models\Product;
use App\Models\SlugRedirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function owner(): User
    {
        return User::where('email', 'owner@korni.local')->firstOrFail();
    }

    private function product(string $slug = 'staryy-adres'): Product
    {
        return Product::create([
            'title' => 'Тестовый товар для редиректа',
            'slug' => $slug,
            'short_description' => 'Проверка редиректа',
            'price_cents' => 10000,
        ]);
    }

    public function test_old_product_url_redirects_permanently_to_new_one(): void
    {
        $product = $this->product();
        $product->update(['slug' => 'novyy-adres']);

        $this->get('/magazin/staryy-adres')
            ->assertStatus(301)
            ->assertRedirect(route('shop.show', 'novyy-adres'));

        $this->get('/magazin/novyy-adres')->assertOk();
    }

    public function test_redirect_chain_is_flattened_to_the_latest_slug(): void
    {
        $product = $this->product('pervyy-adres');

        $product->update(['slug' => 'vtoroy-adres']);
        $product->update(['slug' => 'tretyy-adres']);

        $this->get('/magazin/pervyy-adres')
            ->assertStatus(301)
            ->assertRedirect(route('shop.show', 'tretyy-adres'));

        $this->get('/magazin/vtoroy-adres')
            ->assertStatus(301)
            ->assertRedirect(route('shop.show', 'tretyy-adres'));

        $this->assertSame(
            'tretyy-adres',
            SlugRedirect::query()->where('model_type', Product::class)->where('old_slug', 'pervyy-adres')->value('new_slug'),
        );
    }

    public function test_live_slug_wins_over_stale_alias_when_new_record_claims_it(): void
    {
        $first = $this->product('zanyatyy-adres');
        $first->update(['slug' => 'osvobozhdennyy-adres']);

        $second = $this->product('zanyatyy-adres');

        $this->get('/magazin/zanyatyy-adres')
            ->assertOk()
            ->assertSee($second->title);

        $this->assertDatabaseMissing('slug_redirects', [
            'model_type' => Product::class,
            'old_slug' => 'zanyatyy-adres',
        ]);
    }

    public function test_saving_without_slug_change_does_not_create_redirect(): void
    {
        $product = $this->product('bez-izmeneniy');
        $product->update(['title' => 'Новое название']);

        $this->assertDatabaseMissing('slug_redirects', [
            'model_type' => Product::class,
            'old_slug' => 'bez-izmeneniy',
        ]);

        $this->get('/magazin/bez-izmeneniy')->assertOk();
    }

    public function test_slug_change_in_admin_panel_creates_redirect(): void
    {
        $this->actingAs($this->owner());

        $product = $this->product('adres-do-redaktirovaniya');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['slug' => 'adres-posle-redaktirovaniya'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/magazin/adres-do-redaktirovaniya')
            ->assertStatus(301)
            ->assertRedirect(route('shop.show', 'adres-posle-redaktirovaniya'));
    }

    public function test_masterclass_old_url_redirects(): void
    {
        $masterclass = Masterclass::query()->create([
            'title' => 'Мастер-класс для редиректа',
            'slug' => 'staryy-master-klass',
            'short_description' => 'Проверка',
            'price_cents' => 100000,
        ]);

        $masterclass->update(['slug' => 'novyy-master-klass']);

        $this->get('/master-klassy/staryy-master-klass')
            ->assertStatus(301)
            ->assertRedirect(route('masterclasses.show', 'novyy-master-klass'));
    }

    public function test_course_old_url_redirects(): void
    {
        $course = Course::query()->create([
            'title' => 'Курс для редиректа',
            'slug' => 'staryy-kurs',
            'short_description' => 'Проверка',
            'price_cents' => 100000,
        ]);

        $course->update(['slug' => 'novyy-kurs']);

        $this->get('/kursy/staryy-kurs')
            ->assertStatus(301)
            ->assertRedirect(route('courses.show', 'novyy-kurs'));
    }

    public function test_content_page_old_url_redirects(): void
    {
        $page = ContentPage::query()->create([
            'title' => 'Материал для редиректа',
            'slug' => 'staryy-material',
        ]);

        $page->update(['slug' => 'novyy-material']);

        $this->get('/materialy/staryy-material')
            ->assertStatus(301)
            ->assertRedirect(route('guides.show', 'novyy-material'));
    }

    public function test_legal_document_old_url_redirects(): void
    {
        $document = LegalDocument::query()->create([
            'key' => 'redirect_test_document',
            'title' => 'Документ для редиректа',
            'slug' => 'staryy-dokument',
            'body' => '<p>Текст</p>',
        ]);

        $document->update(['slug' => 'novyy-dokument']);

        $this->get('/dokumenty/staryy-dokument')
            ->assertStatus(301)
            ->assertRedirect(route('legal.show', 'novyy-dokument'));
    }

    public function test_unknown_slug_still_returns_404(): void
    {
        $this->get('/magazin/nesushchestvuyushchiy-tovar')->assertNotFound();
    }

    public function test_post_request_with_old_slug_is_not_redirected(): void
    {
        $masterclass = Masterclass::query()->create([
            'title' => 'Мастер-класс для POST',
            'slug' => 'staryy-post-adres',
            'short_description' => 'Проверка',
            'price_cents' => 100000,
        ]);

        $masterclass->update(['slug' => 'novyy-post-adres']);

        $this->post('/master-klassy/staryy-post-adres/zapis', [])->assertNotFound();
    }
}
