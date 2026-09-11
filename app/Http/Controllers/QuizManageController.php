<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\StoreQuizRequest;
use App\Http\Requests\UpdateQuizRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;

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

    public function store(StoreQuizRequest $request, Course $course, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $lesson->chapter, $course]);

        $validated = $request->validated();

        $lesson->quiz()->create($validated);

        return redirect()->route('coach.courses.lessons.quizzes.index', [$course, $lesson])
            ->with('success', '小テストを作成しました。');
    }

    public function update(UpdateQuizRequest $request, Course $course, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $lesson->chapter, $course]);

        $quiz = $lesson->quiz;
        if (! $quiz) {
            abort(404);
        }

        $validated = $request->validated();

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

    public function storeQuestion(StoreQuestionRequest $request, Course $course, Lesson $lesson)
    {
        $this->authorize('manage', [$lesson, $lesson->chapter, $course]);

        $quiz = $lesson->quiz;
        if (! $quiz) {
            abort(404);
        }

        $validated = $request->validated();

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
