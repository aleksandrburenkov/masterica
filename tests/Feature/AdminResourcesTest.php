<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminResourcesTest extends TestCase
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

    /** @return array<string, array{string}> */
    public static function resourceSlugs(): array
    {
        return [
            'techniques' => ['techniques'],
            'masters' => ['masters'],
            'masterclasses' => ['masterclasses'],
            'workshop-sessions' => ['workshop-sessions'],
            'bookings' => ['bookings'],
            'outreach-requests' => ['outreach-requests'],
            'products' => ['products'],
            'product-variants' => ['product-variants'],
            'stock-movements' => ['stock-movements'],
            'orders' => ['orders'],
            'courses' => ['courses'],
            'course-modules' => ['course-modules'],
            'lessons' => ['lessons'],
            'course-enrollments' => ['course-enrollments'],
            'reviews' => ['reviews'],
            'content-pages' => ['content-pages'],
            'legal-documents' => ['legal-documents'],
            'users' => ['users'],
            'audit-logs' => ['audit-logs'],
        ];
    }

    #[DataProvider('resourceSlugs')]
    public function test_resource_index_pages_load(string $slug): void
    {
        $this->actingAs($this->owner())
            ->get('/admin/'.$slug)
            ->assertOk();
    }

    /** @return array<string, array{string}> */
    public static function createSlugs(): array
    {
        return [
            'techniques' => ['techniques'],
            'masters' => ['masters'],
            'masterclasses' => ['masterclasses'],
            'workshop-sessions' => ['workshop-sessions'],
            'products' => ['products'],
            'product-variants' => ['product-variants'],
            'orders' => ['orders'],
            'courses' => ['courses'],
            'course-modules' => ['course-modules'],
            'lessons' => ['lessons'],
            'course-enrollments' => ['course-enrollments'],
            'reviews' => ['reviews'],
            'content-pages' => ['content-pages'],
            'legal-documents' => ['legal-documents'],
            'users' => ['users'],
        ];
    }

    #[DataProvider('createSlugs')]
    public function test_resource_create_pages_load(string $slug): void
    {
        $this->actingAs($this->owner())
            ->get('/admin/'.$slug.'/create')
            ->assertOk();
    }
}
