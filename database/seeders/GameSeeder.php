<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\Genre;
use App\Models\Screenshot;
use App\Models\User;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        $genreIds = Genre::pluck('id');
        $authors = User::where('is_blocked', false)->where('role', 'user')->get();

        foreach ($authors as $author) {
            Game::factory()->count(2)->create(['user_id' => $author->id])->each(function (Game $game) use ($genreIds) {
                // связь «многие ко многим»: от 1 до 3 жанров у каждой игры
                $game->genres()->attach($genreIds->random(random_int(1, 3))->all());

                // от 1 до 3 скриншотов
                Screenshot::factory()->count(random_int(1, 3))->create(['game_id' => $game->id]);
            });
        }
    }
}
