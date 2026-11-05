<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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

        Log::info('Зарегистрирован новый пользователь', ['user_id' => $user->id]);

        return [$user, $user->createToken('api')->plainTextToken];
    }

    /** Вход по email и паролю. Неверные данные — 401, заблокированный — 403. */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            // пароль в журнал не пишется — только факт неудачной попытки
            Log::warning('Неудачная попытка входа', ['email' => $email]);
            abort(401, 'Неверный email или пароль.');
        }

        if ($user->is_blocked) {
            Log::warning('Попытка входа заблокированного пользователя', ['user_id' => $user->id]);
            abort(403, 'Ваш аккаунт заблокирован.');
        }

        Log::info('Пользователь вошёл в систему', ['user_id' => $user->id]);

        return [$user, $user->createToken('api')->plainTextToken];
    }

    /** Выход: удаляет текущий токен. */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
