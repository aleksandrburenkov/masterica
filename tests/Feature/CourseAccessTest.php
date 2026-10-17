<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\CourseService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([SettingsSeeder::class, CatalogSeeder::class]);
    }

    private function course(): Course
    {
        return Course::where('slug', 'paper-vine-basket-course')->firstOrFail();
    }

    public function test_paid_lesson_requires_active_access(): void
    {
        $course = $this->course();
        $paid = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->where('is_preview', false)
            ->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($user)
            ->get(route('courses.learn', [$course->slug, $paid->id]))
            ->assertRedirect(route('courses.show', $course->slug));
    }

    public function test_preview_lesson_is_accessible_without_enrollment(): void
    {
        $course = $this->course();
        $preview = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->where('is_preview', true)
            ->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);

        $this->actingAs($user)
            ->get(route('courses.learn', [$course->slug, $preview->id]))
            ->assertOk();
    }

    public function test_test_payment_grants_access_and_is_idempotent(): void
    {
        $course = $this->course();
        $user = User::factory()->create(['role' => UserRole::Client]);
        $service = app(CourseService::class);

        $first = $service->grantTestPayment($user, $course);
        $expires = $first->expires_at;

        $second = $service->grantTestPayment($user, $course);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(EnrollmentStatus::Active, $second->status);
        $this->assertTrue($expires->equalTo($second->expires_at));
        $this->assertSame(1, CourseEnrollment::count());
    }

    public function test_active_access_opens_paid_lesson_and_progress(): void
    {
        $course = $this->course();
        $paid = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->where('is_preview', false)
            ->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);

        $service = app(CourseService::class);
        $enrollment = $service->grantTestPayment($user, $course);

        $this->actingAs($user)
            ->get(route('courses.learn', [$course->slug, $paid->id]))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('courses.complete', [$course->slug, $paid->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('lesson_progress', [
            'course_enrollment_id' => $enrollment->id,
            'lesson_id' => $paid->id,
        ]);
        $this->assertGreaterThan(0, $service->progressPercent($enrollment->fresh()));
    }

    public function test_revoked_access_closes_paid_lesson(): void
    {
        $course = $this->course();
        $paid = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->where('is_preview', false)
            ->firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Client]);
        $service = app(CourseService::class);

        $enrollment = $service->grantTestPayment($user, $course);
        $service->revoke($enrollment, null, 'Тестовый отзыв доступа');

        $this->assertFalse($service->hasAccess($user, $paid));
        $this->actingAs($user)
            ->get(route('courses.learn', [$course->slug, $paid->id]))
            ->assertRedirect(route('courses.show', $course->slug));
    }

    public function test_access_remains_after_completing_all_lessons(): void
    {
        $course = $this->course();
        $user = User::factory()->create(['role' => UserRole::Client]);
        $service = app(CourseService::class);
        $enrollment = $service->grantTestPayment($user, $course);

        $paid = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->where('is_preview', false)
            ->firstOrFail();

        $lessons = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
            ->where('is_published', true)
            ->get();

        foreach ($lessons as $lesson) {
            $service->markLessonComplete($enrollment, $lesson);
        }

        $enrollment->refresh();
        $this->assertSame(EnrollmentStatus::Completed, $enrollment->status);
        $this->assertTrue($enrollment->hasActiveAccess());
        $this->assertTrue($service->hasAccess($user, $paid));

        $this->actingAs($user)
            ->get(route('courses.learn', [$course->slug, $paid->id]))
            ->assertOk();
    }
}
