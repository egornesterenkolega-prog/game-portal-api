<?php

namespace App\Services;

use App\Enums\ReactionType;
use App\Models\Game;

class RatingService
{
    /**
     * Рейтинг игры = процент лайков от общего числа оценок; без оценок = 0.
     */
    public function recalculate(Game $game): void
    {
        $total = $game->reactions()->count();
        $likes = $game->reactions()->where('type', ReactionType::Like->value)->count();

        $game->update([
            'rating' => $total > 0 ? round($likes * 100 / $total, 2) : 0,
        ]);
    }
}
