<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatMessageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'body'         => ['required', 'string', 'max:400'],
            'image'        => ['nullable', 'file', 'mimes:jpeg,png'],
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            // 本文
            'body.required' => '本文を入力してください',
            'body.string'   => '本文を入力してください',
            'body.max'      => '本文は400文字以内で入力してください',

            // 画像
            'image.file'    => '画像ファイルを選択してください',
            'image.mimes'   => '「.png」または「.jpeg」形式でアップロードしてください',
        ];
    }

    public function attributes(): array
    {
        return [
            'body'         => '本文',
            'image'        => '画像',
            'remove_image' => '画像削除',
        ];
    }
}