<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            ],
            'image' => [
                'nullable',
                'file',
                'image',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'body.max'    => '本文は' . self::MESSAGE_BODY_MAX_LENGTH . '文字以内で入力してください。',
            'image.image' => '「.png」または「.jpeg」形式でアップロードしてください。',
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $body = (string) $this->input('body', '');
            $file = $this->file('image');

            if ($body === '' && $file === null) {
                $validator->errors()->add('body', '本文を入力してください。');
                return;
            }

            if ($body !== '' && mb_strlen($body, 'UTF-8') > self::MESSAGE_BODY_MAX_LENGTH) {
                $validator->errors()->add(
                    'body',
                    '本文は' . self::MESSAGE_BODY_MAX_LENGTH . '文字以内で入力してください。'
                );
            }

            if ($file !== null) {
                $ext = strtolower((string) $file->getClientOriginalExtension());

                if (!in_array($ext, ['png', 'jpeg'], true)) {
                    $validator->errors()->add('image', '「.png」または「.jpeg」形式でアップロードしてください。');
                    return;
                }

                $info = @getimagesize($file->getRealPath());
                if ($info === false) {
                    $validator->errors()->add('image', '「.png」または「.jpeg」形式でアップロードしてください。');
                }
            }
        });
    }
}