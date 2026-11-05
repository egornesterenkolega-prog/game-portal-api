<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Жанры, блокировка пользователей и статистика (доступны администратору). */
class GenreAndAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_genres(): void
    {
        Genre::factory()->count(3)->create();

        $this->getJson('/api/v1/genres')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_genre(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/genres', ['name' => 'Платформер'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Платформер');

        $this->assertDatabaseHas('genres', ['name' => 'Платформер']);
    }

    public function test_regular_user_cannot_create_genre(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/genres', ['name' => 'Платформер'])->assertStatus(403);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_guest_cannot_create_genre(): void
    {
        $this->postJson('/api/v1/genres', ['name' => 'Платформер'])->assertStatus(401);
    }

    public function test_genre_name_must_be_unique(): void
    {
        Genre::factory()->create(['name' => 'Экшен']);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/genres', ['name' => 'Экшен'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_genre_assigned_to_games_cannot_be_deleted(): void
    {
        $genre = Genre::factory()->create();
        Game::factory()->create()->genres()->attach($genre);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson('/api/v1/genres/'.$genre->id)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Нельзя удалить жанр, который назначен играм.');

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
    }

    public function test_unused_genre_can_be_deleted(): void
    {
        $genre = Genre::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson('/api/v1/genres/'.$genre->id)->assertNoContent();

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
    }

    public function test_admin_can_block_user_and_tokens_are_revoked(): void
    {
        $target = User::factory()->create();
        $target->createToken('api');
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/users/'.$target->id.'/block')->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_blocked' => true]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_admin_cannot_be_blocked(): void
    {
        $other = User::factory()->admin()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/users/'.$other->id.'/block')->assertStatus(403);
    }

    public function test_regular_user_cannot_block_users(): void
    {
        $target = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/users/'.$target->id.'/block')->assertStatus(403);
    }

    public function test_blocked_user_with_valid_token_is_denied(): void
    {
        Sanctum::actingAs(User::factory()->blocked()->create());

        $this->getJson('/api/v1/me')->assertStatus(403);
    }

    public function test_admin_can_view_statistics(): void
    {
        Game::factory()->count(2)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/stats')
            ->assertOk()
            ->assertJsonPath('data.totals.games', 2)
            ->assertJsonStructure(['data' => ['totals', 'games', 'genres', 'top_games', 'top_authors', 'games_by_month']]);
    }

    public function test_regular_user_cannot_view_statistics(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/stats')->assertStatus(403);
    }
}
