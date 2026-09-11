<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_two_categories_with_japanese_only_names_that_share_a_slug(): void
    {
        $this->actingAs($this->admin)->post('/admin/categories', ['name' => 'モバイル']);
        $this->actingAs($this->admin)->post('/admin/categories', ['name' => 'データベース']);

        $this->assertDatabaseCount('categories', 2);

        $mobile = Category::where('name', 'モバイル')->firstOrFail();
        $database = Category::where('name', 'データベース')->firstOrFail();

        $this->assertNotSame($mobile->slug, $database->slug);
    }

    public function test_admin_can_update_category_without_changing_slug_when_name_is_unchanged(): void
    {
        $this->actingAs($this->admin)->post('/admin/categories', ['name' => 'インフラ']);
        $category = Category::where('name', 'インフラ')->firstOrFail();
        $originalSlug = $category->slug;

        $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", ['name' => 'インフラ']);

        $this->assertSame($originalSlug, $category->fresh()->slug);
    }

    public function test_category_creation_fails_validation_without_name(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/categories', []);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_category_update_fails_validation_without_name(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", []);

        $response->assertSessionHasErrors('name');
        $this->assertSame($category->name, $category->fresh()->name);
    }
}
