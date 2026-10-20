<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[DataProvider('emptyGalleries')]
    public function test_gallery_block_is_hidden_without_images(?array $gallery): void
    {
        $product = Product::where('slug', 'basket-paper-vine')->firstOrFail();
        $product->forceFill(['gallery' => $gallery])->save();

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertDontSee('product-gallery__swiper', false)
            ->assertDontSee('data-gallery-src', false);
    }

    public static function emptyGalleries(): array
    {
        return [
            'null' => [null],
            'empty array' => [[]],
            'empty values' => [['']],
        ];
    }

    #[DataProvider('galleryLayouts')]
    public function test_gallery_layout_class_matches_image_count(int $count, string $layout): void
    {
        $gallery = array_map(fn (int $index) => "products/gallery/{$index}.jpg", range(1, $count));

        $product = Product::where('slug', 'basket-paper-vine')->firstOrFail();
        $product->forceFill(['gallery' => $gallery])->save();

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSee("product-gallery--{$layout} product-gallery--count-{$count}", false);
    }

    public static function galleryLayouts(): array
    {
        return [
            'four photos are centered' => [4, 'compact'],
            'five photos fill the width' => [5, 'fill'],
            'six photos fill the width' => [6, 'fill'],
        ];
    }

    public function test_gallery_is_limited_to_six_images(): void
    {
        $gallery = array_map(fn (int $index) => "products/gallery/{$index}.jpg", range(1, 7));

        $product = Product::where('slug', 'basket-paper-vine')->firstOrFail();
        $product->forceFill(['gallery' => $gallery])->save();

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSee('product-gallery--fill product-gallery--count-6', false)
            ->assertSee('storage/products/gallery/6.jpg', false)
            ->assertDontSee('storage/products/gallery/7.jpg', false);
    }

    public function test_gallery_thumbnails_are_rendered_and_stored_as_array(): void
    {
        $gallery = ['products/gallery/first.jpg', 'products/gallery/second.jpg'];

        $product = Product::where('slug', 'basket-paper-vine')->firstOrFail();
        $product->forceFill(['gallery' => $gallery])->save();

        $this->assertSame($gallery, $product->fresh()->gallery);

        $this->get(route('shop.show', $product->slug))
            ->assertOk()
            ->assertSee('data-product-gallery-main', false)
            ->assertSee('data-product-gallery', false)
            ->assertSee('storage/products/gallery/first.jpg', false)
            ->assertSee('storage/products/gallery/second.jpg', false)
            ->assertSee('data-gallery-src', false);
    }

    public function test_product_admin_form_has_gallery_upload(): void
    {
        $owner = User::where('email', 'owner@korni.local')->firstOrFail();

        $this->actingAs($owner)
            ->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Карусель изображений товара')
            ->assertSee('(макс. 6 фото)', false)
            ->assertSee('Превышен лимит загрузок фото');

        app()->setLocale('ru');
        $this->assertSame('Превышен лимит загрузок фото', trans_choice('validation.max.array', 6));
    }

    public function test_admin_rejects_more_than_six_gallery_images(): void
    {
        Storage::fake('public');

        $owner = User::where('email', 'owner@korni.local')->firstOrFail();
        $this->actingAs($owner);

        $images = array_map(
            fn (int $index) => UploadedFile::fake()->image("photo{$index}.jpg", 20, 20),
            range(1, 7),
        );

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'title' => 'Товар с лишними фото',
                'slug' => 'product-with-too-many-photos',
                'short_description' => 'Проверка лимита галереи',
                'price_cents' => 100,
                'gallery' => $images,
            ])
            ->call('create')
            ->assertHasFormErrors(['gallery'])
            ->assertSee('Превышен лимит загрузок фото');
    }
}
