<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'max' => 'Поле «:attribute» не должно быть длиннее :max символов.',
            'email' => 'Укажите корректный email.',
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.min' => 'Пароль должен содержать не менее :min символов.',
            'password.confirmed' => 'Пароль и подтверждение не совпадают.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'имя', 'email' => 'email', 'password' => 'пароль'];
    }
}
