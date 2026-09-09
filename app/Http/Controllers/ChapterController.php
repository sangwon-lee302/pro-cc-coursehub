<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
// use App\Http\Requests\StoreChapterRequest;
// use App\Http\Requests\UpdateChapterRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function store(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

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

    public function update(Request $request, Course $course, Chapter $chapter)
    {
        $this->authorize('manage', [$chapter, $course]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

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

    public function updateOrder(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        // 旧ソート処理（drag & drop 対応前のコード）
        // if ($request->has('direction') && $request->has('chapter_id')) {
        //     $chapter = Chapter::findOrFail($request->chapter_id);
        //     $currentOrder = $chapter->order;
        //     if ($request->direction === 'up' && $currentOrder > 1) {
        //         $swapChapter = Chapter::where('course_id', $course->id)
        //             ->where('order', $currentOrder - 1)->first();
        //         if ($swapChapter) {
        //             $swapChapter->update(['order' => $currentOrder]);
        //             $chapter->update(['order' => $currentOrder - 1]);
        //         }
        //     }
        // }

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', Rule::exists('chapters', 'id')->where('course_id', $course->id)],
        ]);

        foreach ($validated['order'] as $index => $chapterId) {
            Chapter::where('id', $chapterId)->update(['order' => $index + 1]);
        }

        return response()->json(['message' => '並び順を更新しました。']);
    }
}
