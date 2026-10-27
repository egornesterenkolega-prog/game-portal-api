<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:100'],
            'author' => ['sometimes', 'string', 'max:100'],
            'genre_id' => ['sometimes', 'integer', 'exists:genres,id'],
            'min_rating' => ['sometimes', 'numeric', 'between:0,100'],
            'sort' => ['sometimes', Rule::in(['rating', 'created_at', 'price'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'genre_id.exists' => 'Выбранный жанр не существует.',
            'min_rating.between' => 'Минимальный рейтинг должен быть от 0 до 100.',
            'sort.in' => 'Сортировать можно по rating, created_at или price.',
            'direction.in' => 'Направление сортировки: asc или desc.',
            'numeric' => 'Поле «:attribute» должно быть числом.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'string' => 'Поле «:attribute» должно быть строкой.',
        ];
    }
}
