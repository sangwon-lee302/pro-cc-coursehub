<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['role' => 'student']);

        $coach = User::factory()->create(['role' => 'coach']);
        $this->course = Course::factory()->create(['user_id' => $coach->id, 'status' => 'published']);
        $chapter = Chapter::factory()->create(['course_id' => $this->course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->quiz = Quiz::factory()->create(['lesson_id' => $lesson->id]);
    }

    private function enroll(string $status = 'active'): Enrollment
    {
        return Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => $status,
        ]);
    }

    public function test_student_without_active_enrollment_cannot_submit_quiz(): void
    {
        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        $correctOption = Option::factory()->correct()->create(['question_id' => $question->id]);

        $response = $this->actingAs($this->student)->post(
            route('courses.quizzes.submit', [$this->course, $this->quiz]),
            ['answers' => [['question_id' => $question->id, 'option_id' => $correctOption->id]]]
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_enrolled_student_can_submit_quiz_and_score_is_calculated(): void
    {
        $this->enroll();

        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        $correctOption = Option::factory()->correct()->create(['question_id' => $question->id]);
        Option::factory()->create(['question_id' => $question->id]);

        $response = $this->actingAs($this->student)->post(
            route('courses.quizzes.submit', [$this->course, $this->quiz]),
            ['answers' => [['question_id' => $question->id, 'option_id' => $correctOption->id]]]
        );

        $response->assertRedirect(route('courses.quizzes.result', [$this->course, $this->quiz]));
        $this->assertDatabaseHas('submissions', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => 100,
        ]);
    }

    public function test_option_belonging_to_another_question_is_not_counted_as_correct(): void
    {
        $this->enroll();

        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        Option::factory()->create(['question_id' => $question->id]);

        $otherQuestion = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        $foreignCorrectOption = Option::factory()->correct()->create(['question_id' => $otherQuestion->id]);
        Option::factory()->correct()->create(['question_id' => $question->id]);

        $response = $this->actingAs($this->student)->post(
            route('courses.quizzes.submit', [$this->course, $this->quiz]),
            ['answers' => [
                ['question_id' => $question->id, 'option_id' => $foreignCorrectOption->id],
                ['question_id' => $otherQuestion->id],
            ]]
        );

        $response->assertRedirect(route('courses.quizzes.result', [$this->course, $this->quiz]));
        $this->assertDatabaseHas('submissions', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => 0,
        ]);
    }

    public function test_unanswered_question_does_not_crash_submission(): void
    {
        $this->enroll();

        $answeredQuestion = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        $correctOption = Option::factory()->correct()->create(['question_id' => $answeredQuestion->id]);

        $unansweredQuestion = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        Option::factory()->correct()->create(['question_id' => $unansweredQuestion->id]);

        $response = $this->actingAs($this->student)->post(
            route('courses.quizzes.submit', [$this->course, $this->quiz]),
            ['answers' => [
                ['question_id' => $answeredQuestion->id, 'option_id' => $correctOption->id],
                ['question_id' => $unansweredQuestion->id],
            ]]
        );

        $response->assertRedirect(route('courses.quizzes.result', [$this->course, $this->quiz]));
        $this->assertDatabaseHas('submissions', [
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => 50,
        ]);
    }

    public function test_submitting_quiz_with_no_questions_redirects_without_error(): void
    {
        $this->enroll();

        $response = $this->actingAs($this->student)->post(
            route('courses.quizzes.submit', [$this->course, $this->quiz]),
            ['answers' => []]
        );

        $response->assertRedirect(route('courses.show', $this->course));
        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_student_cannot_resubmit_quiz_after_passing(): void
    {
        $this->enroll();

        Submission::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => $this->quiz->passing_score,
        ]);

        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        $correctOption = Option::factory()->correct()->create(['question_id' => $question->id]);

        $response = $this->actingAs($this->student)->post(
            route('courses.quizzes.submit', [$this->course, $this->quiz]),
            ['answers' => [['question_id' => $question->id, 'option_id' => $correctOption->id]]]
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('submissions', 1);
    }

    public function test_student_can_resubmit_quiz_after_failing_with_no_attempt_limit(): void
    {
        $this->enroll();

        $question = Question::factory()->create(['quiz_id' => $this->quiz->id]);
        $correctOption = Option::factory()->correct()->create(['question_id' => $question->id]);
        $wrongOption = Option::factory()->create(['question_id' => $question->id]);

        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($this->student)->post(
                route('courses.quizzes.submit', [$this->course, $this->quiz]),
                ['answers' => [['question_id' => $question->id, 'option_id' => $wrongOption->id]]]
            );

            $response->assertRedirect(route('courses.quizzes.result', [$this->course, $this->quiz]));
        }

        $this->assertDatabaseCount('submissions', 3);
    }

    public function test_result_page_returns_404_when_no_submission_exists(): void
    {
        $this->enroll();

        $response = $this->actingAs($this->student)->get(
            route('courses.quizzes.result', [$this->course, $this->quiz])
        );

        $response->assertStatus(404);
    }

    public function test_result_page_exposes_full_submission_history(): void
    {
        $this->enroll();

        Submission::factory()->create(['user_id' => $this->student->id, 'quiz_id' => $this->quiz->id, 'score' => 10]);
        Submission::factory()->create(['user_id' => $this->student->id, 'quiz_id' => $this->quiz->id, 'score' => 40]);
        Submission::factory()->create(['user_id' => $this->student->id, 'quiz_id' => $this->quiz->id, 'score' => 90]);

        $response = $this->actingAs($this->student)->get(
            route('courses.quizzes.result', [$this->course, $this->quiz])
        );

        $response->assertOk();
        $response->assertViewHas('submissions', fn ($submissions) => $submissions->count() === 3);
        $response->assertSee('10%');
        $response->assertSee('40%');
        $response->assertSee('90%');
    }

    public function test_result_page_uses_latest_submission_for_score(): void
    {
        $this->enroll();

        Submission::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => 10,
            'submitted_at' => now()->subDay(),
        ]);

        $latest = Submission::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => $this->quiz->passing_score,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(
            route('courses.quizzes.result', [$this->course, $this->quiz])
        );

        $response->assertViewHas('submission', fn ($submission) => $submission->id === $latest->id);
    }

    public function test_retake_button_is_shown_when_latest_submission_failed(): void
    {
        $this->enroll();

        Submission::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => $this->quiz->passing_score - 1,
        ]);

        $response = $this->actingAs($this->student)->get(
            route('courses.quizzes.result', [$this->course, $this->quiz])
        );

        $response->assertSee('再受験する');
    }

    public function test_retake_button_is_hidden_when_latest_submission_passed(): void
    {
        $this->enroll();

        Submission::factory()->create([
            'user_id' => $this->student->id,
            'quiz_id' => $this->quiz->id,
            'score' => $this->quiz->passing_score,
        ]);

        $response = $this->actingAs($this->student)->get(
            route('courses.quizzes.result', [$this->course, $this->quiz])
        );

        $response->assertDontSee('再受験する');
    }
}
