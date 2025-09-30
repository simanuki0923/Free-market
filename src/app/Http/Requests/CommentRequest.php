<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 認可はルート側の auth ミドルウェアで担保する想定
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'コメントを入力してください。',
            'body.max'      => 'コメントは255文字以内で入力してください。',
        ];
    }
}
