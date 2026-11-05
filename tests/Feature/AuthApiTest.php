<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Регистрация, вход, выход и профиль. */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Новый игрок',
            'email' => 'new@gameportal.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.email', 'new@gameportal.test')
            ->assertJsonPath('user.role', 'user')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'new@gameportal.test', 'role' => 'user']);
    }

    public function test_registration_validates_input(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => '123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@gameportal.test']);

        $this->postJson('/api/v1/register', [
            'name' => 'Дубликат',
            'email' => 'taken@gameportal.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_login_and_get_token(): void
    {
        User::factory()->create(['email' => 'user@gameportal.test']);

        $this->postJson('/api/v1/login', ['email' => 'user@gameportal.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email', 'role'], 'token']);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        User::factory()->create(['email' => 'user@gameportal.test']);

        $this->postJson('/api/v1/login', ['email' => 'user@gameportal.test', 'password' => 'wrong-password'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Неверный email или пароль.');
    }

    public function test_blocked_user_cannot_login(): void
    {
        User::factory()->blocked()->create(['email' => 'blocked@gameportal.test']);

        $this->postJson('/api/v1/login', ['email' => 'blocked@gameportal.test', 'password' => 'password'])
            ->assertStatus(403);
    }

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Требуется авторизация.');
    }

    public function test_me_returns_current_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_deletes_token(): void
    {
        User::factory()->create(['email' => 'user@gameportal.test']);
        $token = $this->postJson('/api/v1/login', ['email' => 'user@gameportal.test', 'password' => 'password'])
            ->json('token');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->postJson('/api/v1/logout')->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
