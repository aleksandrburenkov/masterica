<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Services\CourseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courses) {}

    public function index(): View
    {
        $courses = Course::query()->published()->with('master')->get();

        return view('pages.courses-index', compact('courses'));
    }

    public function show(Request $request, Course $course): View
    {
        abort_unless($course->is_published, 404);

        $course->load(['modules.lessons' => fn ($q) => $q->where('is_published', true)]);

        $enrollment = $request->user()
            ? $this->courses->enrollmentFor($request->user(), $course)
            : null;

        $progress = $enrollment ? $this->courses->progressPercent($enrollment) : 0;

        return view('pages.course-detail', compact('course', 'enrollment', 'progress'));
    }

    public function enroll(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->is_published, 404);
        $this->authorize('create', CourseEnrollment::class);

        $enrollment = $this->courses->enroll($request->user(), $course);

        return redirect()
            ->route('account.courses')
            ->with('success', 'Заявка на курс создана. Доступ откроется после подтверждения оплаты администратором.');
    }

    public function learn(Request $request, Course $course, Lesson $lesson): View|RedirectResponse
    {
        abort_unless($lesson->module->course_id === $course->id, 404);
        abort_unless($lesson->is_published, 404);

        $user = $request->user();

        if (! $this->courses->hasAccess($user, $lesson)) {
            return redirect()
                ->route('courses.show', $course->slug)
                ->with('error', 'Этот урок доступен после оплаты курса.');
        }

        $enrollment = $this->courses->enrollmentFor($user, $course);

        $course->load(['modules.lessons' => fn ($q) => $q->where('is_published', true)]);

        $completedLessonIds = $enrollment
            ? $enrollment->progress()->whereNotNull('completed_at')->pluck('lesson_id')->all()
            : [];

        return view('pages.course-learn', compact('course', 'lesson', 'enrollment', 'completedLessonIds'));
    }

    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        abort_unless($lesson->module->course_id === $course->id, 404);

        $enrollment = $this->courses->enrollmentFor($request->user(), $course);

        if (! $enrollment) {
            abort(403);
        }

        $this->courses->markLessonComplete($enrollment, $lesson);

        $next = $course->modules()
            ->with(['lessons' => fn ($q) => $q->where('is_published', true)])
            ->get()
            ->flatMap->lessons
            ->firstWhere(fn ($l) => $l->sort > $lesson->sort && $l->module->id >= $lesson->module->id);

        if ($next) {
            return redirect()->route('courses.learn', [$course->slug, $next->id]);
        }

        return redirect()->route('account.courses')->with('success', 'Урок отмечен как пройденный.');
    }
}
