<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReactionService
{
    public function __construct(private RatingService $rating)
    {
    }

    /**
     * Поставить или изменить оценку (у пользователя не более одной на игру)
     * и пересчитать рейтинг игры.
     */
    public function set(User $user, Game $game, string $type): Reaction
    {
        return DB::transaction(function () use ($user, $game, $type) {
            $reaction = Reaction::updateOrCreate(
                ['user_id' => $user->id, 'game_id' => $game->id],
                ['type' => $type],
            );

            $this->rating->recalculate($game);

            return $reaction;
        });
    }

    /** Снять оценку и пересчитать рейтинг. Если оценки нет — 404. */
    public function remove(User $user, Game $game): void
    {
        $reaction = Reaction::where('user_id', $user->id)->where('game_id', $game->id)->first();

        if (! $reaction) {
            abort(404, 'Вы ещё не оценивали эту игру.');
        }

        DB::transaction(function () use ($reaction, $game) {
            $reaction->delete();
            $this->rating->recalculate($game);
        });
    }
}
