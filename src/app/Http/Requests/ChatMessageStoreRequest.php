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
                'required_without:image',
            ],
            'image' => [
                'nullable',
                'file',
                'image',
                'mimetypes:image/png,image/jpeg',
            ],
        ];
    }

    public function messages(): array
    {
        $typeMsg = '「.png」または「.jpeg」形式でアップロードしてください。';

        return [
            'body.required_without'  => '本文を入力してください。',
            'body.max'               => '本文は' . self::MESSAGE_BODY_MAX_LENGTH . '文字以内で入力してください。',
            'image.image'            => '画像ファイルを選択してください。',
            'image.mimetypes'        => $typeMsg,
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
            if ($body !== '' && mb_strlen($body, 'UTF-8') > self::MESSAGE_BODY_MAX_LENGTH) {
                $validator->errors()->add(
                    'body',
                    '本文は' . self::MESSAGE_BODY_MAX_LENGTH . '文字以内で入力してください。'
                );
            }

            $file = $this->file('image');
            if ($file) {
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