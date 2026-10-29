<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Game;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class CommentService
{
    /** Комментарии игры, новые сверху, по 20 на странице, вместе с авторами. */
    public function paginateForGame(Game $game): LengthAwarePaginator
    {
        return $game->comments()->with('user')->latest()->paginate(20);
    }

    public function create(User $user, Game $game, array $data): Comment
    {
        $comment = $game->comments()->create([
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        return $comment->load('user');
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
