<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('comment'));
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
