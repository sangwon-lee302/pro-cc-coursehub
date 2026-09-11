<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create(['name' => '元の名前']);

        $response = $this->actingAs($user)->put('/profile', [
            'name' => '更新後の名前',
            'bio' => '自己紹介文です。',
            'avatar_url' => 'https://example.com/avatar.png',
        ]);

        $response->assertRedirect('/profile');
        $this->assertSame('更新後の名前', $user->fresh()->name);
    }

    public function test_profile_update_fails_validation_without_name(): void
    {
        $user = User::factory()->create(['name' => '元の名前']);

        $response = $this->actingAs($user)->put('/profile', []);

        $response->assertSessionHasErrors('name');
        $this->assertSame('元の名前', $user->fresh()->name);
    }

    public function test_profile_update_fails_validation_with_invalid_avatar_url(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'テストユーザー',
            'avatar_url' => 'not-a-valid-url',
        ]);

        $response->assertSessionHasErrors('avatar_url');
    }
}
