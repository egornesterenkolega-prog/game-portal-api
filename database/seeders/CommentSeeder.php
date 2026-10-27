<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('is_blocked', false)->get();

        foreach (Game::all() as $game) {
            for ($i = 0; $i < random_int(1, 3); $i++) {
                Comment::factory()->create([
                    'user_id' => $users->random()->id,
                    'game_id' => $game->id,
                ]);
            }
        }
    }
}
