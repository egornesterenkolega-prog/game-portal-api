<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Reaction;
use App\Models\User;
use App\Services\RatingService;
use Illuminate\Database\Seeder;

class ReactionSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_blocked', false)->get();
        $ratingService = app(RatingService::class);

        foreach (Game::all() as $game) {
            // у пользователя не более одной оценки на игру
            $users->random(random_int(2, min(6, $users->count())))->each(function (User $user) use ($game) {
                Reaction::factory()->create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                ]);
            });

            $ratingService->recalculate($game);
        }
    }
}
