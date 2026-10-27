<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Введите текст комментария.',
            'body.string' => 'Комментарий должен быть текстом.',
            'body.max' => 'Комментарий не должен быть длиннее :max символов.',
        ];
    }
}
