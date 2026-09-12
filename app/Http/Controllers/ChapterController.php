<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterOrderRequest;
use App\Http\Requests\UpdateChapterRequest;
use App\Models\Chapter;
use App\Models\Course;

class ChapterController extends Controller
{
    public function index(Course $course)
    {
        $this->authorize('update', $course);

        $chapters = $course->chapters()->orderBy('order')->with('lessons')->get();

        return view('coach.chapters.index', compact('course', 'chapters'));
    }

    public function create(Course $course)
    {
        $this->authorize('update', $course);

        return view('coach.chapters.create', compact('course'));
    }

    public function store(StoreChapterRequest $request, Course $course)
    {
        $validated = $request->validated();

        $maxOrder = $course->chapters()->max('order') ?? 0;

        $course->chapters()->create([
            'title' => $validated['title'],
            'order' => $maxOrder + 1,
        ]);

        return redirect()->route('coach.courses.chapters.index', $course)
            ->with('success', 'チャプターを作成しました。');
    }

    public function edit(Course $course, Chapter $chapter)
    {
        $this->authorize('manage', [$chapter, $course]);

        return view('coach.chapters.edit', compact('course', 'chapter'));
    }

    public function update(UpdateChapterRequest $request, Course $course, Chapter $chapter)
    {
        $validated = $request->validated();

        $chapter->update($validated);

        return redirect()->route('coach.courses.chapters.index', $course)
            ->with('success', 'チャプターを更新しました。');
    }

    public function destroy(Course $course, Chapter $chapter)
    {
        $this->authorize('manage', [$chapter, $course]);

        $chapter->delete();

        return redirect()->route('coach.courses.chapters.index', $course)
            ->with('success', 'チャプターを削除しました。');
    }

    public function updateOrder(UpdateChapterOrderRequest $request, Course $course)
    {
        $validated = $request->validated();

        foreach ($validated['order'] as $index => $chapterId) {
            Chapter::where('id', $chapterId)->update(['order' => $index + 1]);
        }

        return response()->json(['message' => '並び順を更新しました。']);
    }
}
