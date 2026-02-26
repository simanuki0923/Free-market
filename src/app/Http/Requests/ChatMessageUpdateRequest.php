<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageUpdateRequest extends FormRequest
{
    private const MESSAGE_BODY_MAX_LENGTH = 400;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:' . self::MESSAGE_BODY_MAX_LENGTH,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => '本文を入力してください。',
            'body.max' => '本文は' . self::MESSAGE_BODY_MAX_LENGTH . '文字以内で入力してください。',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'body' => is_string($this->input('body'))
                ? trim($this->input('body'))
                : $this->input('body'),
        ]);
    }
}