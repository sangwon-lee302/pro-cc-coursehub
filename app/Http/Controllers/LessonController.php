<?php

namespace App\Http\Controllers;

use App\Events\CourseCompleted;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;

class LessonController extends Controller
{
    public function show(Course $course, Lesson $lesson)
    {
        $this->authorize('view', $course);
        $this->authorize('view', [$lesson, $course]);

        $course->load('chapters.lessons');

        $progress = LessonProgress::where('user_id', auth()->id())
            ->where('lesson_id', $lesson->id)
            ->first();

        return view('courses.lessons.show', compact('course', 'lesson', 'progress'));
    }

    public function complete(Course $course, Lesson $lesson)
    {
        $this->authorize('complete', [$lesson, $course]);

        LessonProgress::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'lesson_id' => $lesson->id,
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );

        // 全公開レッスンが完了済みかチェックし、完了ならイベント発火
        $this->checkCourseCompletion($course);

        return redirect()->route('courses.lessons.show', [$course, $lesson])
            ->with('success', 'レッスンを完了しました。');
    }

    /**
     * コースの全公開レッスンが完了済みかを判定し、完了なら CourseCompleted イベントを発火
     */
    private function checkCourseCompletion(Course $course): void
    {
        $user = auth()->user();

        $publishedLessonIds = $course->getAllLessonIds();

        if (empty($publishedLessonIds)) {
            return;
        }

        $completedCount = LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $publishedLessonIds)
            ->where('status', 'completed')
            ->count();

        if ($completedCount >= count($publishedLessonIds)) {
            $enrollment = $user->enrollments()
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->first();

            if ($enrollment) {
                event(new CourseCompleted($enrollment, $user, $course));
            }
        }
    }
}
