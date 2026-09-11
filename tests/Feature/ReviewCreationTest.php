<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $coach;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coach = User::factory()->create(['role' => 'coach']);
        $this->course = Course::factory()->create(['user_id' => $this->coach->id, 'status' => 'published']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    private function enroll(string $status = 'completed'): Enrollment
    {
        return Enrollment::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => $status,
        ]);
    }

    public function test_student_with_completed_enrollment_can_post_review(): void
    {
        $this->enroll();

        $response = $this->actingAs($this->student)->post(
            route('courses.reviews.store', $this->course),
            ['rating' => 5, 'comment' => 'とても良いコースでした']
        );

        $response->assertRedirect(route('courses.show', $this->course));
        $response->assertSessionHas('success', 'レビューを投稿しました。');
        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'rating' => 5,
            'comment' => 'とても良いコースでした',
        ]);
    }

    public function test_student_without_completed_enrollment_cannot_post_review(): void
    {
        $this->enroll('active');

        $response = $this->actingAs($this->student)->post(
            route('courses.reviews.store', $this->course),
            ['rating' => 5]
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_student_who_already_reviewed_cannot_post_again(): void
    {
        $this->enroll();

        Review::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $response = $this->actingAs($this->student)->post(
            route('courses.reviews.store', $this->course),
            ['rating' => 3]
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_coach_cannot_post_review(): void
    {
        $response = $this->actingAs($this->coach)->post(
            route('courses.reviews.store', $this->course),
            ['rating' => 5]
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_admin_cannot_post_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('courses.reviews.store', $this->course),
            ['rating' => 5]
        );

        $response->assertStatus(403);
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_requires_rating(): void
    {
        $this->enroll();

        $response = $this->actingAs($this->student)->post(
            route('courses.reviews.store', $this->course),
            ['comment' => 'コメントのみ']
        );

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_rating_out_of_range_is_rejected(): void
    {
        $this->enroll();

        $response = $this->actingAs($this->student)->post(
            route('courses.reviews.store', $this->course),
            ['rating' => 6]
        );

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_posted_review_is_visible_on_course_show_page_to_other_viewers(): void
    {
        $review = Review::factory()->create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'rating' => 4,
            'comment' => '分かりやすかったです',
        ]);

        $response = $this->actingAs($this->coach)->get(route('courses.show', $this->course));

        $response->assertOk();
        $response->assertSee($this->student->name);
        $response->assertSee($review->comment);
    }

    public function test_coach_cannot_see_review_form_on_course_show_page(): void
    {
        $response = $this->actingAs($this->coach)->get(route('courses.show', $this->course));

        $response->assertOk();
        $response->assertDontSee('レビューを投稿');
    }
}
