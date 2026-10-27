<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /** Регистрация: создаёт пользователя с ролью user и выдаёт токен. */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'], // хешируется приведением 'hashed' в модели
            'role' => UserRole::User,
        ]);

        return [$user, $user->createToken('api')->plainTextToken];
    }

    /** Вход по email и паролю. Неверные данные — 401, заблокированный — 403. */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            abort(401, 'Неверный email или пароль.');
        }

        if ($user->is_blocked) {
            abort(403, 'Ваш аккаунт заблокирован.');
        }

        return [$user, $user->createToken('api')->plainTextToken];
    }

    /** Выход: удаляет текущий токен. */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
