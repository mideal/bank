<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_returns_401(): void
    {
        $this->patch('/api/user')->assertStatus(401);
    }

    public function test_update_user(): void
    {
        $user = User::factory()->create(['age' => 25]);

        $response = $this->actingAs($user)->patchJson('/api/user', [
            'name' => 'Denis',
            'email' => 'denis@example.com',
            'age' => 30,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Denis')
            ->assertJsonPath('data.email', 'denis@example.com')
            ->assertJsonPath('data.age', 30);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Denis',
            'email' => 'denis@example.com',
            'age' => 30,
        ]);
    }

    public function test_update_email_already_taken_returns_422(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/user', [
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.source', 'email');
    }

    public function test_update_age_can_be_set_to_null(): void
    {
        $user = User::factory()->create(['age' => 25]);

        $response = $this->actingAs($user)->patchJson('/api/user', [
            'age' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.age', null);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'age' => null,
        ]);
    }

    public function test_update_name_cannot_be_null(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/user', ['name' => null])
            ->assertStatus(422);
    }

    public function test_update_email_cannot_be_null(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/api/user', ['email' => null])
            ->assertStatus(422);
    }

    public function test_update_with_no_fields_returns_current_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/user', []);

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }
}
