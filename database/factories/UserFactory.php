<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake('ru_RU')->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // в модели включено приведение 'hashed', пароль хешируется автоматически
            'password' => 'password',
            'role' => UserRole::User,
            'is_blocked' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['is_blocked' => true]);
    }
}
