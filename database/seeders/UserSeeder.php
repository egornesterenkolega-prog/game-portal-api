<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Учётные записи для тестирования (пароль у всех: password)
        User::create([
            'name' => 'Администратор',
            'email' => 'admin@gameportal.test',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        User::create([
            'name' => 'Тестовый Пользователь',
            'email' => 'user@gameportal.test',
            'password' => 'password',
            'role' => UserRole::User,
        ]);

        User::create([
            'name' => 'Заблокированный Пользователь',
            'email' => 'blocked@gameportal.test',
            'password' => 'password',
            'role' => UserRole::User,
            'is_blocked' => true,
        ]);

        // Ещё 7 обычных пользователей с правдоподобными данными
        User::factory()->count(7)->create();
    }
}
