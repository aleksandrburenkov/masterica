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

    public function test_existing_slug_gets_an_alphabetic_record_suffix(): void
    {
        Product::where('slug', 'macrame-panel')->firstOrFail()->update([
            'title' => 'Тестовый товар',
            'slug' => 'testovyi-tovar',
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm(['title' => 'Тестовый товар'])
            ->assertFormSet(['slug' => 'testovyi-tovar-tt']);
    }

    public function test_repeated_collision_adds_a_letter_index(): void
    {
        Product::where('slug', 'macrame-panel')->firstOrFail()->update([
            'title' => 'Тестовый товар',
            'slug' => 'testovyi-tovar',
        ]);

        Product::create([
            'title' => 'Тестовый товар',
            'slug' => 'testovyi-tovar-tt',
            'short_description' => 'Дубль',
        ]);

        $this->assertSame('testovyi-tovar-tt-a', Product::uniqueSlug('Тестовый товар'));
    }

    public function test_editing_title_does_not_change_existing_slug(): void
    {
        $product = Product::where('slug', 'macrame-panel')->firstOrFail();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['title' => 'Новое название товара'])
            ->assertFormSet(['slug' => 'macrame-panel']);
    }
}
