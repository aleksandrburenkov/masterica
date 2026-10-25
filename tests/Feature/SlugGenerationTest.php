<?php

namespace Tests\Feature;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\LegalDocuments\Pages\CreateLegalDocument;
use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Masterclasses\Pages\CreateMasterclass;
use App\Filament\Resources\Techniques\Pages\CreateTechnique;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SlugGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->actingAs(User::where('email', 'owner@korni.local')->firstOrFail());
    }

    public function test_masterclass_slug_is_generated_from_title(): void
    {
        Livewire::test(CreateMasterclass::class)
            ->fillForm(['title' => 'Плетение из бумаги'])
            ->assertFormSet(['slug' => 'pletenie-iz-bumagi']);
    }

    public function test_course_slug_is_generated_from_title(): void
    {
        Livewire::test(CreateCourse::class)
            ->fillForm(['title' => 'Курс по макраме'])
            ->assertFormSet(['slug' => 'kurs-po-makrame']);
    }

    public function test_content_page_slug_is_generated_from_title(): void
    {
        Livewire::test(CreateContentPage::class)
            ->fillForm(['title' => 'Бумажная лоза'])
            ->assertFormSet(['slug' => 'bumaznaia-loza']);
    }

    public function test_technique_slug_is_generated_from_name(): void
    {
        Livewire::test(CreateTechnique::class)
            ->fillForm(['name' => 'Керамика'])
            ->assertFormSet(['slug' => 'keramika']);
    }

    public function test_lesson_slug_is_generated_from_title(): void
    {
        $module = CourseModule::query()->orderBy('id')->firstOrFail();

        Livewire::test(CreateLesson::class)
            ->fillForm([
                'course_module_id' => $module->id,
                'title' => 'Подготовка',
            ])
            ->assertFormSet(['slug' => 'podgotovka']);
    }

    public function test_legal_document_slug_is_generated_from_title(): void
    {
        Livewire::test(CreateLegalDocument::class)
            ->fillForm(['title' => 'Правила студии'])
            ->assertFormSet(['slug' => 'pravila-studii']);
    }

    public function test_lesson_slug_is_unique_inside_module_only(): void
    {
        $modules = CourseModule::query()->orderBy('id')->take(2)->get();

        $this->assertCount(2, $modules);

        $first = Lesson::create([
            'course_module_id' => $modules[0]->id,
            'title' => 'Итоговый урок',
        ]);

        $second = Lesson::create([
            'course_module_id' => $modules[1]->id,
            'title' => 'Итоговый урок',
        ]);

        $duplicate = Lesson::create([
            'course_module_id' => $modules[0]->id,
            'title' => 'Итоговый урок',
        ]);

        $this->assertSame('itogovyi-urok', $first->slug);
        $this->assertSame('itogovyi-urok', $second->slug);
        $this->assertSame('itogovyi-urok-2', $duplicate->slug);
    }

    public function test_lesson_scoped_unique_validation_allows_same_slug_in_other_module(): void
    {
        $modules = CourseModule::query()->orderBy('id')->take(2)->get();

        Lesson::create([
            'course_module_id' => $modules[0]->id,
            'title' => 'Повторяющийся урок',
            'slug' => 'povtoriaiushchiisia-urok',
        ]);

        Livewire::test(CreateLesson::class)
            ->fillForm([
                'course_module_id' => $modules[1]->id,
                'title' => 'Повторяющийся урок',
                'slug' => 'povtoriaiushchiisia-urok',
            ])
            ->assertHasNoFormErrors(['slug']);
    }
}
