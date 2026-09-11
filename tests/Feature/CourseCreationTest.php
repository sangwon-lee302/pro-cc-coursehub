<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseCreationTest extends TestCase
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'テストコース',
            'category_id' => $this->category->id,
            'description' => 'テストコースの説明文です。',
            'difficulty' => 'beginner',
            'status' => 'draft',
        ], $overrides);
    }

    public function test_course_creation_sets_published_at_when_status_is_published(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'status' => 'published',
        ]));

        $course = Course::where('title', 'テストコース')->firstOrFail();

        $this->assertNotNull($course->published_at);
    }

    public function test_course_creation_leaves_published_at_null_when_status_is_draft(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'status' => 'draft',
        ]));

        $course = Course::where('title', 'テストコース')->firstOrFail();

        $this->assertNull($course->published_at);
    }

    public function test_course_creation_creates_default_first_chapter(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload());

        $course = Course::where('title', 'テストコース')->firstOrFail();

        $this->assertDatabaseHas('chapters', [
            'course_id' => $course->id,
            'title' => 'はじめに',
            'order' => 1,
        ]);
    }

    public function test_course_creation_syncs_existing_tags(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'tags' => $tags->pluck('id')->toArray(),
        ]));

        $course = Course::where('title', 'テストコース')->firstOrFail();

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('course_tag', [
                'course_id' => $course->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_course_creation_creates_new_tags_from_comma_separated_input(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'new_tags' => 'Vue,Nuxt',
        ]));

        $course = Course::where('title', 'テストコース')->firstOrFail();

        $vue = Tag::where('name', 'Vue')->firstOrFail();
        $nuxt = Tag::where('name', 'Nuxt')->firstOrFail();

        $this->assertDatabaseHas('course_tag', ['course_id' => $course->id, 'tag_id' => $vue->id]);
        $this->assertDatabaseHas('course_tag', ['course_id' => $course->id, 'tag_id' => $nuxt->id]);
    }

    public function test_course_creation_does_not_duplicate_tag_when_new_tag_slug_already_exists(): void
    {
        $existingTag = Tag::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'new_tags' => 'Laravel',
        ]));

        $course = Course::where('title', 'テストコース')->firstOrFail();

        $this->assertDatabaseCount('tags', 1);
        $this->assertDatabaseHas('course_tag', [
            'course_id' => $course->id,
            'tag_id' => $existingTag->id,
        ]);
    }

    public function test_course_creation_stores_uploaded_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'image' => UploadedFile::fake()->image('course.jpg'),
        ]));

        $course = Course::where('title', 'テストコース')->firstOrFail();

        $this->assertNotNull($course->image_path);
        Storage::disk('public')->assertExists($course->image_path);
    }

    public function test_course_creation_generates_slug_from_title(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'title' => 'Laravel Basics',
        ]));

        $this->assertDatabaseHas('courses', [
            'title' => 'Laravel Basics',
            'slug' => Str::slug('Laravel Basics'),
        ]);
    }

    public function test_course_creation_generates_fallback_slug_for_non_ascii_title(): void
    {
        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'title' => '日本語のみのタイトル',
        ]));

        $course = Course::where('title', '日本語のみのタイトル')->firstOrFail();

        $this->assertStringStartsWith('course-', $course->slug);
    }

    public function test_course_creation_increments_slug_when_duplicate_slug_exists_across_coaches(): void
    {
        $otherCoach = User::factory()->create(['role' => 'coach']);
        $title = 'Duplicate Title Course';

        Course::factory()->create([
            'user_id' => $otherCoach->id,
            'category_id' => $this->category->id,
            'title' => $title,
            'slug' => Str::slug($title),
        ]);

        $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'title' => $title,
        ]));

        $newCourse = Course::where('title', $title)
            ->where('user_id', $this->coach->id)
            ->firstOrFail();

        $this->assertSame(Str::slug($title).'-1', $newCourse->slug);
    }

    public function test_course_creation_fails_with_duplicate_title_for_same_coach(): void
    {
        Course::factory()->create([
            'user_id' => $this->coach->id,
            'category_id' => $this->category->id,
            'title' => 'テストコース',
        ]);

        $response = $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload());

        $response->assertSessionHasErrors('title');
        $this->assertDatabaseCount('courses', 1);
    }

    public function test_course_creation_fails_validation_when_required_fields_are_missing(): void
    {
        $response = $this->actingAs($this->coach)->post('/coach/courses', []);

        $response->assertSessionHasErrors(['title', 'category_id', 'description', 'difficulty', 'status']);
    }

    public function test_course_creation_fails_validation_with_invalid_image_mime_type(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'image' => UploadedFile::fake()->create('document.pdf', 100),
        ]));

        $response->assertSessionHasErrors('image');
    }

    public function test_course_creation_fails_validation_with_oversized_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->coach)->post('/coach/courses', $this->validPayload([
            'image' => UploadedFile::fake()->image('big.jpg')->size(3000),
        ]));

        $response->assertSessionHasErrors('image');
    }
}
