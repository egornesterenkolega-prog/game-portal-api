<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // доступ ограничивается middleware auth:sanctum
    }

    public function rules(): array
    {
        return [
            // название уникально в пределах одного автора
            'title' => [
                'required', 'string', 'max:255',
                Rule::unique('games', 'title')->where('user_id', $this->user()?->id),
            ],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'genre_ids' => ['required', 'array', 'min:1', 'max:3'],
            'genre_ids.*' => ['integer', 'distinct', 'exists:genres,id'],
            'screenshots' => ['required', 'array', 'min:1', 'max:10'],
            'screenshots.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],   // до 5 МБ
            'game_file' => ['required', 'file', 'mimes:zip', 'max:512000'],  // до 500 МБ
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Поле «:attribute» обязательно для заполнения.',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'numeric' => 'Поле «:attribute» должно быть числом.',
            'array' => 'Поле «:attribute» должно быть списком.',
            'integer' => 'Значение в поле «:attribute» должно быть целым числом.',
            'distinct' => 'Жанры не должны повторяться.',
            'file' => 'Поле «:attribute» должно быть файлом.',
            'title.max' => 'Название не должно быть длиннее :max символов.',
            'title.unique' => 'У вас уже есть игра с таким названием.',
            'description.max' => 'Описание не должно быть длиннее :max символов.',
            'price.min' => 'Цена не может быть отрицательной.',
            'genre_ids.min' => 'Выберите хотя бы один жанр.',
            'genre_ids.max' => 'Можно выбрать не более :max жанров.',
            'genre_ids.*.exists' => 'Выбранный жанр не существует.',
            'screenshots.min' => 'Загрузите хотя бы один скриншот.',
            'screenshots.max' => 'Можно загрузить не более :max скриншотов.',
            'screenshots.*.mimes' => 'Скриншот должен быть в формате JPG или PNG.',
            'screenshots.*.max' => 'Размер скриншота не должен превышать 5 МБ.',
            'game_file.mimes' => 'Файл игры должен быть в формате ZIP.',
            'game_file.max' => 'Размер файла игры не должен превышать 500 МБ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'название',
            'description' => 'описание',
            'price' => 'цена',
            'genre_ids' => 'жанры',
            'screenshots' => 'скриншоты',
            'game_file' => 'файл игры',
        ];
    }
}
