<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Платформер', 'Головоломка', 'Экшен', 'Стратегия', 'Приключение', 'Гонки', 'Ролевая игра', 'Симулятор'] as $name) {
            Genre::firstOrCreate(['name' => $name]);
        }
    }
}
