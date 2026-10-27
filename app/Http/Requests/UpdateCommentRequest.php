<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // проверка «только автор комментария» будет в Policy
    }

    public function rules(): array
    {
        return (new StoreCommentRequest())->rules();
    }

    public function messages(): array
    {
        return (new StoreCommentRequest())->messages();
    }
}
