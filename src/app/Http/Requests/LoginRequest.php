<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ログイン前の画面なので true でOK
        return true;
    }

    /**
     * バリデーションルール
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'メールアドレス必須',
            'email.email'       => 'メールアドレスが正しくありません。',
            'password.required' => 'パスワード必須',
        ];
    }

    /**
     * 表示名（任意）
     */
    public function attributes(): array
    {
        return [
            'email'    => 'メールアドレス',
            'password' => 'パスワード',
        ];
    }

    /**
     * 前処理（任意）：前後の空白除去・小文字化など
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        if (is_string($email)) {
            $this->merge([
                'email' => mb_strtolower(trim($email)),
            ]);
        }
    }
}
