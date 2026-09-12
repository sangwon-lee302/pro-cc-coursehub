<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coach = User::factory()->create(['role' => 'coach']);
        $this->course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => Category::factory()->create()->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
    }

    public function test_coach_can_create_quiz(): void
    {
        $response = $this->actingAs($this->coach)->post(
            route('coach.courses.lessons.quizzes.store', [$this->course, $this->lesson]),
            ['title' => '確認テスト', 'passing_score' => 80]
        );

        $response->assertRedirect(route('coach.courses.lessons.quizzes.index', [$this->course, $this->lesson]));
        $this->assertDatabaseHas('quizzes', [
            'lesson_id' => $this->lesson->id,
            'title' => '確認テスト',
            'passing_score' => 80,
        ]);
    }

    public function test_quiz_creation_fails_validation_without_title(): void
    {
        $response = $this->actingAs($this->coach)->post(
            route('coach.courses.lessons.quizzes.store', [$this->course, $this->lesson]),
            ['passing_score' => 80]
        );

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_quiz_creation_fails_validation_with_passing_score_out_of_range(): void
    {
        $response = $this->actingAs($this->coach)->post(
            route('coach.courses.lessons.quizzes.store', [$this->course, $this->lesson]),
            ['title' => '確認テスト', 'passing_score' => 150]
        );

        $response->assertSessionHasErrors('passing_score');
        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_coach_can_update_quiz(): void
    {
        Quiz::factory()->create(['lesson_id' => $this->lesson->id, 'title' => '元のタイトル']);

        $response = $this->actingAs($this->coach)->put(
            route('coach.courses.lessons.quizzes.update', [$this->course, $this->lesson]),
            ['title' => '更新後のタイトル', 'passing_score' => 90]
        );

        $response->assertRedirect(route('coach.courses.lessons.quizzes.index', [$this->course, $this->lesson]));
        $this->assertSame('更新後のタイトル', $this->lesson->quiz()->first()->title);
    }

    public function test_quiz_update_fails_validation_without_title(): void
    {
        $quiz = Quiz::factory()->create(['lesson_id' => $this->lesson->id, 'title' => '元のタイトル']);

        $response = $this->actingAs($this->coach)->put(
            route('coach.courses.lessons.quizzes.update', [$this->course, $this->lesson]),
            ['passing_score' => 90]
        );

        $response->assertSessionHasErrors('title');
        $this->assertSame('元のタイトル', $quiz->fresh()->title);
    }

    public function test_coach_can_add_question_to_quiz(): void
    {
        Quiz::factory()->create(['lesson_id' => $this->lesson->id]);

        $response = $this->actingAs($this->coach)->post(
            route('coach.courses.lessons.quizzes.questions.store', [$this->course, $this->lesson]),
            [
                'body' => '問題文',
                'options' => [
                    ['body' => '選択肢1'],
                    ['body' => '選択肢2'],
                ],
                'correct_option' => 0,
            ]
        );

        $response->assertRedirect(route('coach.courses.lessons.quizzes.index', [$this->course, $this->lesson]));
        $this->assertDatabaseHas('questions', ['body' => '問題文']);
        $this->assertDatabaseHas('options', ['body' => '選択肢1', 'is_correct' => true]);
        $this->assertDatabaseHas('options', ['body' => '選択肢2', 'is_correct' => false]);
    }

    public function test_question_creation_fails_validation_with_fewer_than_two_options(): void
    {
        Quiz::factory()->create(['lesson_id' => $this->lesson->id]);

        $response = $this->actingAs($this->coach)->post(
            route('coach.courses.lessons.quizzes.questions.store', [$this->course, $this->lesson]),
            [
                'body' => '問題文',
                'options' => [
                    ['body' => '選択肢1'],
                ],
                'correct_option' => 0,
            ]
        );

        $response->assertSessionHasErrors('options');
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_coach_can_delete_quiz(): void
    {
        Quiz::factory()->create(['lesson_id' => $this->lesson->id]);

        $response = $this->actingAs($this->coach)->delete(
            route('coach.courses.lessons.quizzes.destroy', [$this->course, $this->lesson])
        );

        $response->assertRedirect(route('coach.courses.lessons.quizzes.index', [$this->course, $this->lesson]));
        $this->assertNull($this->lesson->quiz()->first());
    }

    public function test_coach_can_delete_question(): void
    {
        $quiz = Quiz::factory()->create(['lesson_id' => $this->lesson->id]);
        $question = Question::factory()->create(['quiz_id' => $quiz->id]);

        $response = $this->actingAs($this->coach)->delete(
            route('coach.courses.lessons.quizzes.questions.destroy', [$this->course, $this->lesson, $question])
        );

        $response->assertRedirect(route('coach.courses.lessons.quizzes.index', [$this->course, $this->lesson]));
        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }
}
