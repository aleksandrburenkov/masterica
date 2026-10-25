<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());
    }

    public function test_slug_is_generated_from_title_in_latin_characters(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['title' => 'Декоративное панно в технике макраме'])
            ->assertFormSet(['slug' => 'dekorativnoe-panno-v-texnike-makrame']);
    }

    public function test_slug_strips_service_symbols_and_collapses_separators(): void
    {
        $this->assertSame(
            'korzina-iz-bumaznoi-lozy-2026',
            Product::uniqueSlug('Корзина!!! из   бумажной лозы / 2026'),
        );
    }

    public function test_slug_has_a_fallback_when_title_has_no_letters_or_digits(): void
    {
        $this->assertSame('tovar', Product::slugFallback());
        $this->assertSame('tovar', Product::uniqueSlug('!!!'));
    }

    public function test_existing_slug_gets_a_numeric_record_suffix(): void
    {
        Product::where('slug', 'macrame-panel')->firstOrFail()->update([
            'title' => 'Тестовый товар',
            'slug' => 'testovyi-tovar',
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm(['title' => 'Тестовый товар'])
            ->assertFormSet(['slug' => 'testovyi-tovar-2']);
    }

    public function test_repeated_collision_increments_the_numeric_suffix(): void
    {
        Product::where('slug', 'macrame-panel')->firstOrFail()->update([
            'title' => 'Тестовый товар',
            'slug' => 'testovyi-tovar',
        ]);

        Product::create([
            'title' => 'Тестовый товар',
            'slug' => 'testovyi-tovar-2',
            'short_description' => 'Дубль',
        ]);

        $this->assertSame('testovyi-tovar-3', Product::uniqueSlug('Тестовый товар'));
    }

    public function test_manual_slug_is_normalized_on_blur(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['slug' => 'Мой Товар / 2026'])
            ->assertFormSet(['slug' => 'moi-tovar-2026']);
    }

    public function test_editing_title_does_not_change_existing_slug(): void
    {
        $product = Product::where('slug', 'macrame-panel')->firstOrFail();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['title' => 'Новое название товара'])
            ->assertFormSet(['slug' => 'macrame-panel']);
    }

    public function test_generated_suffix_respects_max_slug_length(): void
    {
        $title = str_repeat('a', 200);

        $first = Product::uniqueSlug($title);
        $this->assertSame(Product::slugMaxLength(), strlen($first));

        Product::create([
            'title' => $title,
            'slug' => $first,
            'short_description' => 'Длинный слаг',
            'price_cents' => 100,
        ]);

        $second = Product::uniqueSlug($title);

        $this->assertLessThanOrEqual(Product::slugMaxLength(), strlen($second));
        $this->assertStringEndsWith('-2', $second);
    }

    public function test_slug_is_generated_by_model_when_form_value_is_missing(): void
    {
        $product = Product::create([
            'title' => 'Плетёная ваза',
            'short_description' => 'Ручная работа',
            'price_cents' => 100000,
        ]);

        $this->assertSame('pletenaia-vaza', $product->slug);
    }
}
