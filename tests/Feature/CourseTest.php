<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    private User $student;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coach = User::factory()->create(['role' => 'coach']);
        $this->student = User::factory()->create(['role' => 'student']);
        $this->category = Category::factory()->create();
    }

    public function test_student_can_view_course_list(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->student)->get('/courses');

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_student_can_view_published_course(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->student)->get("/courses/{$course->id}");

        $response->assertStatus(200);
        $response->assertSee($course->title);
    }

    public function test_student_cannot_view_draft_course(): void
    {
        $course = Course::factory()->draft()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->student)->get("/courses/{$course->id}");

        $response->assertStatus(403);
    }

    public function test_student_cannot_view_archived_course(): void
    {
        $course = Course::factory()->archived()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->student)->get("/courses/{$course->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_course_regardless_of_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['draft', 'published', 'archived'] as $status) {
            $course = Course::factory()->create([
                'user_id' => $this->coach->id,
                'category_id' => $this->category->id,
                'status' => $status,
            ]);

            $response = $this->actingAs($admin)->get("/courses/{$course->id}");

            $response->assertStatus(200);
        }
    }

    public function test_coach_can_view_own_course_regardless_of_status(): void
    {
        foreach (['draft', 'published', 'archived'] as $status) {
            $course = Course::factory()->create([
                'user_id' => $this->coach->id,
                'category_id' => $this->category->id,
                'status' => $status,
            ]);

            $response = $this->actingAs($this->coach)->get("/courses/{$course->id}");

            $response->assertStatus(200);
        }
    }

    public function test_coach_cannot_view_other_coaches_non_published_course(): void
    {
        $otherCoach = User::factory()->create(['role' => 'coach']);

        foreach (['draft', 'archived'] as $status) {
            $course = Course::factory()->create([
                'user_id' => $otherCoach->id,
                'category_id' => $this->category->id,
                'status' => $status,
            ]);

            $response = $this->actingAs($this->coach)->get("/courses/{$course->id}");

            $response->assertStatus(403);
        }
    }

    public function test_coach_can_view_other_coaches_published_course(): void
    {
        $otherCoach = User::factory()->create(['role' => 'coach']);
        $course = Course::factory()->create([
            'user_id' => $otherCoach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->coach)->get("/courses/{$course->id}");

        $response->assertStatus(200);
    }

    public function test_coach_can_create_course(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $response = $this->actingAs($this->coach)->post('/coach/courses', [
            'title' => 'テストコース',
            'category_id' => $this->category->id,
            'description' => 'テストコースの説明文です。',
            'difficulty' => 'beginner',
            'status' => 'draft',
            'tags' => $tags->pluck('id')->toArray(),
        ]);

        $response->assertRedirect('/coach/courses');
        $this->assertDatabaseHas('courses', [
            'title' => 'テストコース',
            'user_id' => $this->coach->id,
        ]);
    }

    public function test_coach_can_update_course(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->coach)->put("/coach/courses/{$course->id}", [
            'title' => '更新されたタイトル',
            'category_id' => $this->category->id,
            'description' => '更新された説明文です。',
            'difficulty' => 'intermediate',
            'status' => 'published',
        ]);

        $response->assertRedirect('/coach/courses');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => '更新されたタイトル',
        ]);
    }

    public function test_course_update_fails_validation_when_required_fields_are_missing(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'title' => '元のタイトル',
        ]);

        $response = $this->actingAs($this->coach)->put("/coach/courses/{$course->id}", []);

        $response->assertSessionHasErrors(['title', 'category_id', 'description', 'difficulty', 'status']);
        $this->assertSame('元のタイトル', $course->fresh()->title);
    }

    public function test_coach_can_delete_own_course(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->coach)->delete("/coach/courses/{$course->id}");

        $response->assertRedirect('/coach/courses');
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
    }

    public function test_coach_cannot_delete_other_coaches_course(): void
    {
        $otherCoach = User::factory()->create(['role' => 'coach']);
        $course = Course::factory()->create([
            'user_id' => $otherCoach->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->coach)->delete("/coach/courses/{$course->id}");

        $response->assertStatus(403);
    }

    public function test_student_cannot_create_course(): void
    {
        $response = $this->actingAs($this->student)->get('/coach/courses/create');

        $response->assertStatus(403);
    }

    public function test_course_list_can_be_filtered_by_category(): void
    {
        $otherCategory = Category::factory()->create();

        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'title' => 'カテゴリAのコース',
        ]);

        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $otherCategory->id,
            'status' => 'published',
            'title' => 'カテゴリBのコース',
        ]);

        $response = $this->actingAs($this->student)
            ->get("/courses?category={$this->category->id}");

        $response->assertStatus(200);
        $response->assertSee('カテゴリAのコース');
        $response->assertDontSee('カテゴリBのコース');
    }

    public function test_course_list_can_be_searched(): void
    {
        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'title' => 'Laravel入門',
        ]);

        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'title' => 'React基礎',
        ]);

        $response = $this->actingAs($this->student)
            ->get('/courses?search=Laravel');

        $response->assertStatus(200);
        $response->assertSee('Laravel入門');
        $response->assertDontSee('React基礎');
    }

    public function test_progress_rate_reaches_100_percent_with_unpublished_lessons(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);

        $publishedLessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);
        Lesson::factory()->unpublished()->create(['chapter_id' => $chapter->id]);

        foreach ($publishedLessons as $lesson) {
            LessonProgress::factory()->create([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
                'status' => 'completed',
            ]);
        }

        $this->assertSame(100, $course->getProgressRate($this->student->id));
    }

    public function test_progress_rate_counts_only_published_lessons(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);

        $publishedLessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);
        Lesson::factory()->unpublished()->create(['chapter_id' => $chapter->id]);

        LessonProgress::factory()->create([
            'user_id' => $this->student->id,
            'lesson_id' => $publishedLessons->first()->id,
            'status' => 'completed',
        ]);

        $this->assertSame(50, $course->getProgressRate($this->student->id));
    }

    public function test_progress_rate_is_zero_when_no_published_lessons_exist(): void
    {
        $course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);

        Lesson::factory()->unpublished()->count(2)->create(['chapter_id' => $chapter->id]);

        $this->assertSame(0, $course->getProgressRate($this->student->id));
    }
}
