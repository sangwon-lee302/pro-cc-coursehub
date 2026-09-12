<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossCourseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $coachA;

    private User $coachB;

    private Course $courseA;

    private Chapter $chapterA;

    private Lesson $lessonA;

    private Quiz $quizA;

    private Course $courseB;

    private Chapter $chapterB;

    private Lesson $lessonB;

    private Quiz $quizB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['role' => 'student']);
        $category = Category::factory()->create();

        $this->coachA = User::factory()->create(['role' => 'coach']);
        $this->courseA = Course::factory()->create(['user_id' => $this->coachA->id, 'category_id' => $category->id, 'status' => 'published']);
        $this->chapterA = Chapter::factory()->create(['course_id' => $this->courseA->id]);
        $this->lessonA = Lesson::factory()->create(['chapter_id' => $this->chapterA->id]);
        $this->quizA = Quiz::factory()->create(['lesson_id' => $this->lessonA->id]);

        $this->coachB = User::factory()->create(['role' => 'coach']);
        $this->courseB = Course::factory()->create(['user_id' => $this->coachB->id, 'category_id' => $category->id, 'status' => 'published']);
        $this->chapterB = Chapter::factory()->create(['course_id' => $this->courseB->id]);
        $this->lessonB = Lesson::factory()->create(['chapter_id' => $this->chapterB->id]);
        $this->quizB = Quiz::factory()->create(['lesson_id' => $this->lessonB->id]);

        Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->courseA->id,
            'status' => 'active',
        ]);
    }

    public function test_student_cannot_view_quiz_belonging_to_another_course(): void
    {
        $response = $this->actingAs($this->student)->get(
            route('courses.quizzes.show', [$this->courseA, $this->quizB])
        );

        $response->assertStatus(403);
    }

    public function test_student_cannot_submit_quiz_belonging_to_another_course(): void
    {
        $question = Question::factory()->create(['quiz_id' => $this->quizB->id]);

        $response = $this->actingAs($this->student)->post(
            route('courses.quizzes.submit', [$this->courseA, $this->quizB]),
            ['answers' => [['question_id' => $question->id, 'option_id' => null]]]
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('submissions', 0);
    }

    public function test_student_cannot_view_result_of_quiz_belonging_to_another_course(): void
    {
        $response = $this->actingAs($this->student)->get(
            route('courses.quizzes.result', [$this->courseA, $this->quizB])
        );

        $response->assertStatus(403);
    }

    public function test_student_cannot_view_lesson_belonging_to_another_course(): void
    {
        $response = $this->actingAs($this->student)->get(
            route('courses.lessons.show', [$this->courseA, $this->lessonB])
        );

        $response->assertStatus(403);
    }

    public function test_student_cannot_complete_lesson_belonging_to_another_course(): void
    {
        $response = $this->actingAs($this->student)->post(
            route('courses.lessons.complete', [$this->courseA, $this->lessonB])
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_coach_cannot_update_another_coachs_chapter_via_own_course(): void
    {
        $response = $this->actingAs($this->coachA)->put(
            route('coach.courses.chapters.update', [$this->courseA, $this->chapterB]),
            ['title' => 'Hijacked title']
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('chapters', ['id' => $this->chapterB->id, 'title' => 'Hijacked title']);
    }

    public function test_coach_cannot_destroy_another_coachs_chapter_via_own_course(): void
    {
        $response = $this->actingAs($this->coachA)->delete(
            route('coach.courses.chapters.destroy', [$this->courseA, $this->chapterB])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('chapters', ['id' => $this->chapterB->id]);
    }

    public function test_coach_cannot_reorder_another_coachs_chapters(): void
    {
        $response = $this->actingAs($this->coachA)->postJson(
            route('coach.courses.chapters.order', $this->courseA),
            ['order' => [$this->chapterB->id]]
        );

        $response->assertStatus(422);
        $this->assertDatabaseHas('chapters', ['id' => $this->chapterB->id, 'order' => $this->chapterB->order]);
    }

    public function test_coach_cannot_update_lesson_belonging_to_another_coachs_chapter(): void
    {
        $response = $this->actingAs($this->coachA)->put(
            route('coach.courses.chapters.lessons.update', [$this->courseA, $this->chapterA, $this->lessonB]),
            ['title' => 'Hijacked title', 'body' => 'Hijacked body', 'is_published' => true]
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('lessons', ['id' => $this->lessonB->id, 'title' => 'Hijacked title']);
    }

    public function test_coach_cannot_create_quiz_on_another_coachs_lesson(): void
    {
        $lessonWithoutQuiz = Lesson::factory()->create(['chapter_id' => $this->chapterB->id]);

        $response = $this->actingAs($this->coachA)->post(
            route('coach.courses.lessons.quizzes.store', [$this->courseA, $lessonWithoutQuiz]),
            ['title' => 'Hijacked quiz', 'passing_score' => 70]
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('quizzes', ['lesson_id' => $lessonWithoutQuiz->id]);
    }

    public function test_coach_cannot_destroy_quiz_belonging_to_another_coachs_lesson(): void
    {
        $response = $this->actingAs($this->coachA)->delete(
            route('coach.courses.lessons.quizzes.destroy', [$this->courseA, $this->lessonB])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('quizzes', ['id' => $this->quizB->id]);
    }

    public function test_coach_cannot_destroy_question_belonging_to_another_coachs_lesson(): void
    {
        $questionB = Question::factory()->create(['quiz_id' => $this->quizB->id]);

        $response = $this->actingAs($this->coachA)->delete(
            route('coach.courses.lessons.quizzes.questions.destroy', [$this->courseA, $this->lessonA, $questionB])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('questions', ['id' => $questionB->id]);
    }
}
