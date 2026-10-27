<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('genres', 'name')->ignore($this->route('genre')),
            ],
        ];
    }

    public function messages(): array
    {
        return (new StoreGenreRequest())->messages();
    }
}
