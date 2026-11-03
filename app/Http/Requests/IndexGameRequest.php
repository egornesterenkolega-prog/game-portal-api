<?php

namespace App\Http\Requests;

use App\Models\Game;
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
            'price_from' => ['sometimes', 'numeric', 'min:0'],
            'price_to' => ['sometimes', 'numeric', 'min:0', 'gte:price_from'],
            'sort' => ['sometimes', Rule::in(Game::SORTABLE)],
            'order' => ['sometimes', Rule::in(['asc', 'desc'])],
            'direction' => ['sometimes', Rule::in(['asc', 'desc'])], // синоним order
            'per_page' => ['sometimes', 'integer', 'between:1,50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'genre_id.exists' => 'Выбранный жанр не существует.',
            'min_rating.between' => 'Минимальный рейтинг должен быть от 0 до 100.',
            'price_from.min' => 'Цена «от» не может быть отрицательной.',
            'price_to.gte' => 'Цена «до» не может быть меньше цены «от».',
            'sort.in' => 'Сортировать можно по: rating, created_at, price, title.',
            'order.in' => 'Направление сортировки: asc или desc.',
            'direction.in' => 'Направление сортировки: asc или desc.',
            'per_page.between' => 'Размер страницы должен быть от 1 до 50.',
            'numeric' => 'Поле «:attribute» должно быть числом.',
            'integer' => 'Поле «:attribute» должно быть целым числом.',
            'string' => 'Поле «:attribute» должно быть строкой.',
        ];
    }
}
