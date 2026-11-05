<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Game;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReactionService
{
    public function __construct(private RatingService $rating)
    {
    }

    /**
     * Поставить или изменить оценку (не более одной на игру) и пересчитать рейтинг.
     * Бизнес-правило: нельзя оценивать собственную игру.
     * Строка игры блокируется, чтобы параллельные оценки не испортили рейтинг.
     */
    public function set(User $user, Game $game, string $type): Reaction
    {
        if ($game->user_id === $user->id) {
            Log::warning('Попытка оценить собственную игру', ['user_id' => $user->id, 'game_id' => $game->id]);

            throw new BusinessException('Нельзя оценивать собственную игру.');
        }

        return DB::transaction(function () use ($user, $game, $type) {
            Game::whereKey($game->id)->lockForUpdate()->first();

            $reaction = Reaction::updateOrCreate(
                ['user_id' => $user->id, 'game_id' => $game->id],
                ['type' => $type],
            );

            $this->rating->recalculate($game);
            Log::info('Игра оценена', ['game_id' => $game->id, 'user_id' => $user->id, 'type' => $type]);

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
            Game::whereKey($game->id)->lockForUpdate()->first();
            $reaction->delete();
            $this->rating->recalculate($game);
        });
    }
}
