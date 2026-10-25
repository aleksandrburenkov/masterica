<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Masterclasses\Pages\EditMasterclass;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\ContentPage;
use App\Models\Course;
use App\Models\Master;
use App\Models\Masterclass;
use App\Models\Product;
use App\Models\User;
use App\Services\MediaFileService;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class MediaFileCleanupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh');
        Storage::fake('public');
    }

    public function test_deleting_product_removes_cover_and_every_gallery_file(): void
    {
        $this->storeFiles([
            'products/cover.jpg',
            'products/gallery/first.jpg',
            'products/gallery/second.jpg',
        ]);

        $product = $this->createProduct([
            'image' => 'products/cover.jpg',
            'gallery' => ['products/gallery/first.jpg', 'products/gallery/second.jpg'],
        ]);

        $product->delete();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing('products/cover.jpg');
        Storage::disk('public')->assertMissing('products/gallery/first.jpg');
        Storage::disk('public')->assertMissing('products/gallery/second.jpg');
    }

    public function test_deleting_single_image_records_removes_their_files(): void
    {
        $models = [
            $this->createMasterclass(['image' => 'masterclasses/cover.jpg']),
            $this->createMaster(['photo' => 'masters/photo.jpg']),
            $this->createCourse(['image' => 'courses/cover.jpg']),
            $this->createContentPage(['image' => 'content/cover.jpg']),
        ];

        $this->storeFiles([
            'masterclasses/cover.jpg',
            'masters/photo.jpg',
            'courses/cover.jpg',
            'content/cover.jpg',
        ]);

        foreach ($models as $model) {
            $model->delete();
        }

        Storage::disk('public')->assertMissing('masterclasses/cover.jpg');
        Storage::disk('public')->assertMissing('masters/photo.jpg');
        Storage::disk('public')->assertMissing('courses/cover.jpg');
        Storage::disk('public')->assertMissing('content/cover.jpg');
    }

    public function test_missing_files_do_not_prevent_record_deletion(): void
    {
        $product = $this->createProduct([
            'image' => 'products/missing.jpg',
            'gallery' => ['products/gallery/missing.jpg'],
        ]);

        $this->assertTrue($product->delete());
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_file_shared_by_two_records_is_deleted_only_after_the_last_record(): void
    {
        $this->storeFiles(['products/shared.jpg']);

        $first = $this->createProduct(['image' => 'products/shared.jpg']);
        $second = $this->createProduct(['image' => 'products/shared.jpg']);

        $first->delete();

        Storage::disk('public')->assertExists('products/shared.jpg');

        $second->delete();

        Storage::disk('public')->assertMissing('products/shared.jpg');
    }

    public function test_file_shared_between_different_models_is_preserved_until_both_are_deleted(): void
    {
        $this->storeFiles(['products/shared-with-course.jpg']);

        $product = $this->createProduct(['image' => 'products/shared-with-course.jpg']);
        $course = $this->createCourse(['image' => 'products/shared-with-course.jpg']);

        $product->delete();

        Storage::disk('public')->assertExists('products/shared-with-course.jpg');

        $course->delete();

        Storage::disk('public')->assertMissing('products/shared-with-course.jpg');
    }

    public function test_legacy_public_assets_and_unmanaged_paths_are_never_deleted(): void
    {
        $this->storeFiles(['images/photo-1.jpg', 'uploads/private.jpg']);

        $masterclass = $this->createMasterclass(['image' => 'images/photo-1.jpg']);
        $product = $this->createProduct(['image' => 'uploads/private.jpg']);

        $masterclass->delete();
        $product->delete();

        Storage::disk('public')->assertExists('images/photo-1.jpg');
        Storage::disk('public')->assertExists('uploads/private.jpg');
    }

    public function test_external_urls_and_traversal_paths_are_ignored(): void
    {
        $service = app(MediaFileService::class);

        $this->assertNull($service->normalizePath('https://example.com/photo.jpg'));
        $this->assertNull($service->normalizePath('//example.com/photo.jpg'));
        $this->assertNull($service->normalizePath('products/../images/photo-1.jpg'));
        $this->assertNull($service->normalizePath('products//photo.jpg'));
        $this->assertSame('products/photo.jpg', $service->normalizePath('/storage/products/photo.jpg'));
    }

    public function test_files_are_deleted_after_a_committed_transaction(): void
    {
        $this->storeFiles(['products/transaction.jpg']);

        $product = $this->createProduct(['image' => 'products/transaction.jpg']);

        DB::transaction(fn () => $product->delete());

        Storage::disk('public')->assertMissing('products/transaction.jpg');
    }

    public function test_files_survive_a_rolled_back_transaction(): void
    {
        $this->storeFiles(['products/rollback.jpg']);

        $product = $this->createProduct(['image' => 'products/rollback.jpg']);

        try {
            DB::transaction(function () use ($product): void {
                $product->delete();

                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        Storage::disk('public')->assertExists('products/rollback.jpg');
    }

    public function test_filament_delete_action_removes_the_image(): void
    {
        $this->storeFiles(['masterclasses/admin.jpg']);

        $masterclass = $this->createMasterclass(['image' => 'masterclasses/admin.jpg']);

        $this->actingAs(User::factory()->create(['role' => UserRole::Owner->value]));

        Livewire::test(EditMasterclass::class, ['record' => $masterclass->getRouteKey()])
            ->callAction('delete');

        $this->assertDatabaseMissing('masterclasses', ['id' => $masterclass->id]);
        Storage::disk('public')->assertMissing('masterclasses/admin.jpg');
    }

    public function test_filament_bulk_delete_removes_product_files(): void
    {
        $this->storeFiles(['products/bulk.jpg', 'products/gallery/bulk.jpg']);

        $product = $this->createProduct([
            'image' => 'products/bulk.jpg',
            'gallery' => ['products/gallery/bulk.jpg'],
        ]);

        $this->actingAs(User::factory()->create(['role' => UserRole::Owner->value]));

        Livewire::test(ListProducts::class)
            ->callTableBulkAction(DeleteBulkAction::class, [$product]);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing('products/bulk.jpg');
        Storage::disk('public')->assertMissing('products/gallery/bulk.jpg');
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function storeFiles(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'image');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProduct(array $attributes = []): Product
    {
        static $counter = 0;

        return Product::create($attributes + [
            'title' => 'Тестовый товар '.++$counter,
            'slug' => 'test-product-'.$counter,
            'short_description' => 'Описание',
            'price_cents' => 1000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMasterclass(array $attributes = []): Masterclass
    {
        static $counter = 0;

        return Masterclass::create($attributes + [
            'title' => 'Тестовый мастер-класс '.++$counter,
            'slug' => 'test-masterclass-'.$counter,
            'short_description' => 'Описание',
            'price_cents' => 1000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMaster(array $attributes = []): Master
    {
        static $counter = 0;

        return Master::create($attributes + [
            'name' => 'Тестовый мастер '.++$counter,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCourse(array $attributes = []): Course
    {
        static $counter = 0;

        return Course::create($attributes + [
            'title' => 'Тестовый курс '.++$counter,
            'slug' => 'test-course-'.$counter,
            'short_description' => 'Описание',
            'price_cents' => 1000,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createContentPage(array $attributes = []): ContentPage
    {
        static $counter = 0;

        return ContentPage::create($attributes + [
            'title' => 'Тестовая страница '.++$counter,
            'slug' => 'test-page-'.$counter,
        ]);
    }
}
