<?php

namespace App\Http\Requests;

use App\Enums\ReactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ReactionType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Укажите оценку: like или dislike.',
            'type.enum' => 'Оценка должна быть like или dislike.',
        ];
    }
}
