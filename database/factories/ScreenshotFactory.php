<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\Screenshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Screenshot>
 */
class ScreenshotFactory extends Factory
{
    protected $model = Screenshot::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'path' => 'screenshots/'.fake()->unique()->uuid().'.'.fake()->randomElement(['png', 'jpg']),
        ];
    }
}
