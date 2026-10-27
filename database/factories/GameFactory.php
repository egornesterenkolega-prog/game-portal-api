<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /** Все сочетания «прилагательное + существительное» — для уникальных названий. */
    private static function titles(): array
    {
        $adjectives = ['Тёмный', 'Лунный', 'Пиксельный', 'Забытый', 'Быстрый', 'Тихий', 'Хрустальный', 'Ржавый', 'Северный', 'Последний'];
        $nouns = ['лабиринт', 'побег', 'рубеж', 'остров', 'город', 'паладин', 'маршрут', 'храм', 'патруль', 'сад'];

        $titles = [];
        foreach ($adjectives as $adjective) {
            foreach ($nouns as $noun) {
                $titles[] = $adjective.' '.$noun;
            }
        }

        return $titles;
    }

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->unique()->randomElement(self::titles()),
            'description' => fake('ru_RU')->realText(200),
            'price' => fake()->randomElement([0, 0, 49, 99, 149, 199.50, 299, 499]),
            'file_path' => 'games/'.fake()->unique()->uuid().'.zip',
            'rating' => 0, // пересчитывается RatingService после появления оценок
        ];
    }
}
