<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_gallery_thumbnails_are_rendered_and_stored_as_array(): void
    {        $gallery = ['products/gallery/first.jpg', 'products/gallery/second.jpg'];

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
            ->assertSee('Карусель изображений товара');
    }
}
