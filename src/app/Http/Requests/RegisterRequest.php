<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // ゲストが使う想定なので true
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:20'],
            'email'                 => ['required', 'string', 'email:filter', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            // confirmed を使うと password_confirmation と一致チェックが入る
            'password_confirmation' => ['required', 'string', 'min:8'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                  => 'ユーザー名',
            'email'                 => 'メールアドレス',
            'password'              => 'パスワード',
            'password_confirmation' => '確認用パスワード',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'お名前を入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email'    => 'メールアドレスはメール形式で入力してください',
            'password.required'  => 'パスワードを入力してください',
            'password.min'       => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードと一致しません',
            'password_confirmation.required' => '確認用パスワードを入力してください',
            'password_confirmation.min'      => '確認用パスワードは8文字以上で入力してください',
        ];
    }

}
