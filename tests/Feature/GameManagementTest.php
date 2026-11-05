<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Публикация, изменение и удаление игр, права доступа, бизнес-правило «лимит игр». */
class GameManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // файлы пишутся во временное хранилище, настоящая папка storage не засоряется
        Storage::fake('local');
        Storage::fake('public');
    }

    private function payload(array $override = []): array
    {
        $genre = Genre::factory()->create();

        return array_merge([
            'title' => 'Тестовая игра',
            'description' => 'Описание тестовой игры',
            'price' => 99,
            'genre_ids' => [$genre->id],
            'screenshots' => [UploadedFile::fake()->create('shot.jpg', 50, 'image/jpeg')],
            'game_file' => UploadedFile::fake()->create('game.zip', 100, 'application/zip'),
        ], $override);
    }

    public function test_guest_cannot_publish_game(): void
    {
        $this->postJson('/api/v1/games', [])->assertStatus(401);
    }

    public function test_user_can_publish_game(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/games', $this->payload(), ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Тестовая игра')
            ->assertJsonPath('data.author.id', $user->id)
            ->assertJsonCount(1, 'data.genres')
            ->assertJsonCount(1, 'data.screenshots');

        $this->assertDatabaseHas('games', ['title' => 'Тестовая игра', 'user_id' => $user->id]);
        $this->assertDatabaseCount('game_genre', 1);
        $this->assertDatabaseCount('screenshots', 1);
    }

    public function test_publish_validates_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/v1/games', ['price' => -5], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'price', 'genre_ids', 'screenshots', 'game_file']);
    }

    public function test_publish_rejects_more_than_three_genres(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $genres = Genre::factory()->count(4)->create()->pluck('id')->all();

        $this->post('/api/v1/games', $this->payload(['genre_ids' => $genres]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['genre_ids']);
    }

    public function test_author_cannot_exceed_game_limit(): void
    {
        config(['portal.max_games_per_user' => 2]);
        $user = User::factory()->create();
        Game::factory()->count(2)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->post('/api/v1/games', $this->payload(), ['Accept' => 'application/json'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Достигнут лимит: автор может опубликовать не более 2 игр.');

        $this->assertDatabaseCount('games', 2);
    }

    public function test_author_can_update_own_game(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id, 'title' => 'Старое название']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/games/'.$game->id, ['title' => 'Новое название'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Новое название');

        $this->assertDatabaseHas('games', ['id' => $game->id, 'title' => 'Новое название']);
    }

    public function test_user_cannot_update_foreign_game(): void
    {
        $game = Game::factory()->create(['title' => 'Чужая игра']);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/games/'.$game->id, ['title' => 'Взлом'])
            ->assertStatus(403);

        $this->assertDatabaseHas('games', ['id' => $game->id, 'title' => 'Чужая игра']);
    }

    public function test_author_can_delete_own_game(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/games/'.$game->id)->assertNoContent();

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    }

    public function test_admin_can_delete_any_game(): void
    {
        $game = Game::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson('/api/v1/games/'.$game->id)->assertNoContent();

        $this->assertDatabaseMissing('games', ['id' => $game->id]);
    }

    public function test_user_cannot_delete_foreign_game(): void
    {
        $game = Game::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson('/api/v1/games/'.$game->id)->assertStatus(403);

        $this->assertDatabaseHas('games', ['id' => $game->id]);
    }

    public function test_blocked_user_cannot_publish_game(): void
    {
        Sanctum::actingAs(User::factory()->blocked()->create());

        $this->post('/api/v1/games', $this->payload(), ['Accept' => 'application/json'])
            ->assertStatus(403);
    }
}
