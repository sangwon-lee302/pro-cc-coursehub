<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_update_user_role(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}/role", [
            'role' => 'coach',
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertSame('coach', $user->fresh()->role);
    }

    public function test_user_role_update_fails_validation_with_invalid_role(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}/role", [
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame('student', $user->fresh()->role);
    }

    public function test_user_role_update_fails_validation_without_role(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($this->admin)->put("/admin/users/{$user->id}/role", []);

        $response->assertSessionHasErrors('role');
        $this->assertSame('student', $user->fresh()->role);
    }
}
