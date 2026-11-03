<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Comment;
use App\Models\Game;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CommentService
{
    /** Комментарии игры, новые сверху, по 20 на странице, вместе с авторами. */
    public function paginateForGame(Game $game): LengthAwarePaginator
    {
        return $game->comments()->with('user')->latest()->paginate(20);
    }

    /**
     * Бизнес-правило: не более N комментариев одного пользователя к одной игре
     * (config portal.max_comments_per_game). Проверка и вставка — в транзакции
     * с блокировкой строки игры.
     */
    public function create(User $user, Game $game, array $data): Comment
    {
        return DB::transaction(function () use ($user, $game, $data) {
            Game::whereKey($game->id)->lockForUpdate()->first();

            $limit = (int) config('portal.max_comments_per_game');
            if ($game->comments()->where('user_id', $user->id)->count() >= $limit) {
                throw new BusinessException("Можно оставить не более {$limit} комментариев к одной игре.");
            }

            $comment = $game->comments()->create([
                'user_id' => $user->id,
                'body' => $data['body'],
            ]);

            return $comment->load('user');
        });
    }

    public function update(Comment $comment, array $data): Comment
    {
        $comment->update(['body' => $data['body']]);

        return $comment->load('user');
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
