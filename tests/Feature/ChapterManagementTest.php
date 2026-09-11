<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coach = User::factory()->create(['role' => 'coach']);
        $this->course = Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => Category::factory()->create()->id,
        ]);
    }

    public function test_coach_can_create_chapter(): void
    {
        $response = $this->actingAs($this->coach)->post(
            route('coach.courses.chapters.store', $this->course),
            ['title' => '新しいチャプター']
        );

        $response->assertRedirect(route('coach.courses.chapters.index', $this->course));
        $this->assertDatabaseHas('chapters', [
            'course_id' => $this->course->id,
            'title' => '新しいチャプター',
        ]);
    }

    public function test_chapter_creation_fails_validation_without_title(): void
    {
        $response = $this->actingAs($this->coach)->post(
            route('coach.courses.chapters.store', $this->course),
            []
        );

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('chapters', 0);
    }

    public function test_coach_can_update_chapter(): void
    {
        $chapter = Chapter::factory()->create(['course_id' => $this->course->id, 'title' => '元のタイトル']);

        $response = $this->actingAs($this->coach)->put(
            route('coach.courses.chapters.update', [$this->course, $chapter]),
            ['title' => '更新後のタイトル']
        );

        $response->assertRedirect(route('coach.courses.chapters.index', $this->course));
        $this->assertSame('更新後のタイトル', $chapter->fresh()->title);
    }

    public function test_chapter_update_fails_validation_without_title(): void
    {
        $chapter = Chapter::factory()->create(['course_id' => $this->course->id, 'title' => '元のタイトル']);

        $response = $this->actingAs($this->coach)->put(
            route('coach.courses.chapters.update', [$this->course, $chapter]),
            []
        );

        $response->assertSessionHasErrors('title');
        $this->assertSame('元のタイトル', $chapter->fresh()->title);
    }

    public function test_coach_can_reorder_chapters(): void
    {
        $first = Chapter::factory()->create(['course_id' => $this->course->id, 'order' => 1]);
        $second = Chapter::factory()->create(['course_id' => $this->course->id, 'order' => 2]);

        $response = $this->actingAs($this->coach)->postJson(
            route('coach.courses.chapters.order', $this->course),
            ['order' => [$second->id, $first->id]]
        );

        $response->assertOk();
        $this->assertSame(1, $second->fresh()->order);
        $this->assertSame(2, $first->fresh()->order);
    }

    public function test_chapter_reorder_fails_validation_when_order_is_missing(): void
    {
        $response = $this->actingAs($this->coach)->postJson(
            route('coach.courses.chapters.order', $this->course),
            []
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('order');
    }
}
