<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Оценки (лайк/дизлайк, рейтинг) и комментарии, бизнес-правила 2 и 3. */
class ReactionAndCommentTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Оценки ----------

    public function test_user_can_like_foreign_game_and_rating_is_recalculated(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/games/{$game->id}/reaction", ['type' => 'like'])
            ->assertStatus(201)
            ->assertJsonPath('data.type', 'like');

        $this->assertDatabaseHas('reactions', ['user_id' => $user->id, 'game_id' => $game->id, 'type' => 'like']);
        $this->assertEquals(100.0, (float) $game->fresh()->rating);
    }

    public function test_changing_reaction_keeps_one_row_and_updates_rating(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/games/{$game->id}/reaction", ['type' => 'like'])->assertStatus(201);
        $this->putJson("/api/v1/games/{$game->id}/reaction", ['type' => 'dislike'])->assertOk();

        $this->assertDatabaseCount('reactions', 1);
        $this->assertEquals(0.0, (float) $game->fresh()->rating);
    }

    public function test_user_cannot_rate_own_game(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/games/{$game->id}/reaction", ['type' => 'like'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Нельзя оценивать собственную игру.');

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_reaction_type_is_validated(): void
    {
        $game = Game::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/v1/games/{$game->id}/reaction", ['type' => 'love'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_removing_missing_reaction_returns_404(): void
    {
        $game = Game::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/games/{$game->id}/reaction")->assertStatus(404);
    }

    // ---------- Комментарии ----------

    public function test_guest_can_read_comments(): void
    {
        $game = Game::factory()->create();
        Comment::factory()->count(3)->create(['game_id' => $game->id]);

        $this->getJson("/api/v1/games/{$game->id}/comments")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_add_comment(): void
    {
        $game = Game::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/games/{$game->id}/comments", ['body' => 'Отличная игра!'])
            ->assertStatus(201)
            ->assertJsonPath('data.body', 'Отличная игра!');

        $this->assertDatabaseHas('comments', ['game_id' => $game->id, 'user_id' => $user->id, 'body' => 'Отличная игра!']);
    }

    public function test_comment_body_is_required(): void
    {
        $game = Game::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/v1/games/{$game->id}/comments", ['body' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_comment_limit_per_game_returns_409(): void
    {
        config(['portal.max_comments_per_game' => 2]);
        $game = Game::factory()->create();
        $user = User::factory()->create();
        Comment::factory()->count(2)->create(['game_id' => $game->id, 'user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/games/{$game->id}/comments", ['body' => 'Лишний комментарий'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Можно оставить не более 2 комментариев к одной игре.');

        $this->assertDatabaseCount('comments', 2);
    }

    public function test_user_cannot_edit_foreign_comment(): void
    {
        $comment = Comment::factory()->create(['body' => 'Чужой текст']);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/comments/'.$comment->id, ['body' => 'Взлом'])->assertStatus(403);

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Чужой текст']);
    }

    public function test_admin_can_delete_foreign_comment(): void
    {
        $comment = Comment::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson('/api/v1/comments/'.$comment->id)->assertNoContent();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
