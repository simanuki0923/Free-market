<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageStoreRequest extends FormRequest
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
                'nullable',
                'string',
                'max:' . self::MESSAGE_BODY_MAX_LENGTH,
                'required_without:image',
            ],
            'image' => [
                'nullable',
                'file',
                'mimes:jpeg,png',
                'required_without:body',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without' => '本文を入力してください。',
            'body.max' => '本文は' . self::MESSAGE_BODY_MAX_LENGTH . '文字以内で入力してください。',
            'image.required_without' => '画像を選択してください。',
            'image.mimes' => '「.png」または「.jpeg」形式でアップロードしてください。',
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