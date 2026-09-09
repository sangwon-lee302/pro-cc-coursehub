<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['role' => 'student']);

        $coach = User::factory()->create(['role' => 'coach']);
        $this->course = Course::factory()->create(['user_id' => $coach->id, 'status' => 'published']);
        $chapter = Chapter::factory()->create(['course_id' => $this->course->id]);
        $this->lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
    }

    public function test_unenrolled_student_cannot_complete_lesson(): void
    {
        $response = $this->actingAs($this->student)->post(
            route('courses.lessons.complete', [$this->course, $this->lesson])
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_student_with_cancelled_enrollment_cannot_complete_lesson(): void
    {
        Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->student)->post(
            route('courses.lessons.complete', [$this->course, $this->lesson])
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_enrolled_student_can_complete_lesson(): void
    {
        Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->student)->post(
            route('courses.lessons.complete', [$this->course, $this->lesson])
        );

        $response->assertRedirect(route('courses.lessons.show', [$this->course, $this->lesson]));
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'status' => 'completed',
        ]);
    }
}
