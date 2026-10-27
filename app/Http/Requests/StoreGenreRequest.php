<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // только администратор — проверка в Policy/middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:genres,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Введите название жанра.',
            'name.string' => 'Название жанра должно быть текстом.',
            'name.max' => 'Название жанра не должно быть длиннее :max символов.',
            'name.unique' => 'Такой жанр уже существует.',
        ];
    }
}
