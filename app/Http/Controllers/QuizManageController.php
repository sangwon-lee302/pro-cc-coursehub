<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Http\Request;

class QuizManageController extends Controller
{
    public function index(Course $course, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $lesson->chapter, $course]);

        $quiz = $lesson->quiz;
        if ($quiz) {
            $quiz->load('questions.options');
        }

        return view('coach.quizzes.index', compact('course', 'lesson', 'quiz'));
    }

    public function store(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $lesson->chapter, $course]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $lesson->quiz()->create($validated);

        return redirect()->route('coach.courses.lessons.quizzes.index', [$course, $lesson])
            ->with('success', '小テストを作成しました。');
    }

    public function update(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $lesson->chapter, $course]);

        $quiz = $lesson->quiz;
        if (! $quiz) {
            abort(404);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $quiz->update($validated);

        return redirect()->route('coach.courses.lessons.quizzes.index', [$course, $lesson])
            ->with('success', '小テストを更新しました。');
    }

    public function destroy(Course $course, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $lesson->chapter, $course]);

        $quiz = $lesson->quiz;
        if ($quiz) {
            $quiz->delete();
        }

        return redirect()->route('coach.courses.lessons.quizzes.index', [$course, $lesson])
            ->with('success', '小テストを削除しました。');
    }

    public function storeQuestion(Request $request, Course $course, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $lesson->chapter, $course]);

        $quiz = $lesson->quiz;
        if (! $quiz) {
            abort(404);
        }

        $validated = $request->validate([
            'body' => ['required', 'string'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.body' => ['required', 'string'],
            'options.*.is_correct' => ['boolean'],
            'correct_option' => ['required', 'integer'],
        ]);

        $maxOrder = $quiz->questions()->max('order') ?? 0;

        $question = $quiz->questions()->create([
            'body' => $validated['body'],
            'order' => $maxOrder + 1,
        ]);

        foreach ($validated['options'] as $index => $optionData) {
            $question->options()->create([
                'body' => $optionData['body'],
                'is_correct' => $index == $validated['correct_option'],
            ]);
        }

        return redirect()->route('coach.courses.lessons.quizzes.index', [$course, $lesson])
            ->with('success', '問題を追加しました。');
    }

    public function destroyQuestion(Course $course, Lesson $lesson, Question $question)
    {
        $this->authorize('manage', [$question, $lesson, $course]);

        $question->delete();

        return redirect()->route('coach.courses.lessons.quizzes.index', [$course, $lesson])
            ->with('success', '問題を削除しました。');
    }
}
