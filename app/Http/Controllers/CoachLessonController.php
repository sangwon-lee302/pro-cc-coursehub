<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;

class CoachLessonController extends Controller
{
    public function index(Course $course, Chapter $chapter)
    {
        $this->authorize('manage', [$chapter, $course]);

        $lessons = $chapter->lessons()->orderBy('order')->get();

        return view('coach.lessons.index', compact('course', 'chapter', 'lessons'));
    }

    public function create(Course $course, Chapter $chapter)
    {
        $this->authorize('manage', [$chapter, $course]);

        return view('coach.lessons.create', compact('course', 'chapter'));
    }

    public function store(StoreLessonRequest $request, Course $course, Chapter $chapter)
    {
        $maxOrder = $chapter->lessons()->max('order') ?? 0;

        $chapter->lessons()->create([
            'title' => $request->validated()['title'],
            'body' => $request->validated()['body'],
            'is_published' => $request->boolean('is_published', true),
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('coach.courses.chapters.lessons.index', [$course, $chapter])
            ->with('success', 'レッスンを作成しました。');
    }

    public function edit(Course $course, Chapter $chapter, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $chapter, $course]);

        return view('coach.lessons.edit', compact('course', 'chapter', 'lesson'));
    }

    public function update(UpdateLessonRequest $request, Course $course, Chapter $chapter, Lesson $lesson)
    {
        $lesson->update([
            'title' => $request->validated()['title'],
            'body' => $request->validated()['body'],
            'is_published' => $request->boolean('is_published', true),
        ]);

        return redirect()->route('coach.courses.chapters.lessons.index', [$course, $chapter])
            ->with('success', 'レッスンを更新しました。');
    }

    public function destroy(Course $course, Chapter $chapter, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $chapter, $course]);

        $lesson->delete();

        return redirect()->route('coach.courses.chapters.lessons.index', [$course, $chapter])
            ->with('success', 'レッスンを削除しました。');
    }
}
