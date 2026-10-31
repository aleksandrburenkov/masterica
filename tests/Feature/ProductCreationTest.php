<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());
    }

    private function createProduct(array $state = []): Product
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(array_merge([
                'title' => 'Новый товар',
                'short_description' => 'Короткое описание',
                'price_cents' => 1500,
            ], $state))
            ->call('create')
            ->assertHasNoFormErrors();

        return Product::query()->where('title', $state['title'] ?? 'Новый товар')->firstOrFail();
    }

    public function test_sku_is_generated_from_title_in_the_create_form(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['title' => 'Декоративное панно в технике макраме'])
            ->assertFormSet(['sku' => 'DEKORATIVNOE-PANNO-V-TEXNIKE-MAKRAME']);
    }

    public function test_manual_sku_is_normalized_on_blur(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm(['sku' => 'мой артикул 2026'])
            ->assertFormSet(['sku' => 'MOI-ARTIKUL-2026']);
    }

    public function test_sku_collision_gets_a_numeric_suffix(): void
    {
        Product::create([
            'title' => 'Плетёная ваза',
            'short_description' => 'Ручная работа',
            'price_cents' => 100000,
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm(['title' => 'Плетёная ваза'])
            ->assertFormSet(['sku' => 'PLETENAIA-VAZA-2']);
    }

    public function test_sku_collision_with_warehouse_record_is_rejected(): void
    {
        $product = Product::where('slug', 'basket-paper-vine')->firstOrFail();
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Особый размер',
            'sku' => 'LEGACY-WAREHOUSE-SKU',
            'price_cents' => 1000,
            'stock' => 0,
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'title' => 'Ещё один товар',
                'short_description' => 'Описание',
                'price_cents' => 100,
            ])
            ->fillForm(['sku' => 'LEGACY-WAREHOUSE-SKU'])
            ->call('create')
            ->assertHasFormErrors(['sku']);
    }

    public function test_creating_product_provisions_a_stock_record_with_initial_stock(): void
    {
        $product = $this->createProduct([
            'title' => 'Ваза с акцией',
            'price_cents' => 1500,
            'sale_price_cents' => 1200,
            'sizes' => '20×15 см',
            'initial_stock' => 5,
        ]);

        $this->assertSame(strtoupper($product->slug), $product->sku);

        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        $this->assertSame('20×15 см', $variant->name);
        $this->assertSame($product->sku, $variant->sku);
        // В складскую запись попадает акционная (действующая) цена.
        $this->assertSame(120000, $variant->price_cents);
        $this->assertSame(5, $variant->stock);
        $this->assertSame(5, $product->fresh()->totalStock());

        $this->assertSame(1, $variant->movements()->where('type', StockMovementType::Receipt->value)->count());
    }

    public function test_product_appears_in_warehouse_stock_listing(): void
    {
        $product = $this->createProduct(['title' => 'Товар на складе', 'initial_stock' => 3]);

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'stock' => 3,
        ]);

        $this->get('/admin/product-variants')->assertOk()->assertSee($product->sku);
    }

    public function test_sale_price_is_shown_as_the_main_price_with_struck_base(): void
    {
        $product = $this->createProduct([
            'title' => 'Акционный товар',
            'price_cents' => 2000,
            'sale_price_cents' => 1500,
            'initial_stock' => 1,
        ]);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSee('price__old', false)
            ->assertSee('price__sale', false)
            ->assertSee('2 000 ₽', false)
            ->assertSee('1 500 ₽', false);

        // В корзину товар идёт по акционной цене.
        $this->assertEquals(150000, $product->defaultVariant()->price_cents);
        $this->assertSame(150000, $product->effectivePriceCents());
        $this->assertTrue($product->hasSalePrice());
    }

    public function test_product_without_sale_price_has_no_struck_price(): void
    {
        $product = $this->createProduct(['title' => 'Обычный товар', 'price_cents' => 1000]);

        $this->assertFalse($product->hasSalePrice());
        $this->assertSame(100000, $product->effectivePriceCents());

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertDontSee('price__old', false);
    }

    public function test_sale_price_above_base_is_rejected(): void
    {
        Livewire::test(CreateProduct::class)
            ->fillForm([
                'title' => 'Странная цена',
                'short_description' => 'Описание',
                'price_cents' => 1000,
                'sale_price_cents' => 2000,
            ])
            ->call('create')
            ->assertHasFormErrors(['sale_price_cents']);
    }

    public function test_editing_product_does_not_create_duplicate_stock_records(): void
    {
        $product = Product::where('slug', 'basket-paper-vine')->firstOrFail();
        $before = $product->variants()->count();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['title' => 'Корзина из бумажной лозы (обновлено)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame($before, $product->variants()->count());
        $this->assertSame($product->price_cents, $product->defaultVariant()->price_cents);
        $this->assertSame($product->sku, $product->defaultVariant()->sku);
    }

    public function test_sizes_field_is_saved_on_the_product(): void
    {
        $product = $this->createProduct(['title' => 'Товар с размерами', 'sizes' => '30×40 см']);

        $this->assertSame('30×40 см', $product->fresh()->sizes);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSee('Размеры: 30×40 см');
    }

    public function test_negative_sort_order_is_allowed(): void
    {
        $product = $this->createProduct(['title' => 'Товар в начало списка', 'sort' => -1]);

        $this->assertSame(-1, $product->fresh()->sort);
    }
}
