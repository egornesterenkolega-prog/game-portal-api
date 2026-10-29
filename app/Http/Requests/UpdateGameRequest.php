<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('game'));
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('games', 'title')
                    ->where('user_id', $this->user()?->id)
                    ->ignore($this->route('game')),
            ],
            'description' => ['sometimes', 'required', 'string', 'max:5000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99999999.99'],
            'genre_ids' => ['sometimes', 'required', 'array', 'min:1', 'max:3'],
            'genre_ids.*' => ['integer', 'distinct', 'exists:genres,id'],
            'screenshots' => ['sometimes', 'array', 'max:10'],
            'screenshots.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'game_file' => ['sometimes', 'file', 'mimes:zip', 'max:512000'],
        ];
    }

    public function messages(): array
    {
        return (new StoreGameRequest())->messages();
    }

    public function attributes(): array
    {
        return (new StoreGameRequest())->attributes();
    }
}
