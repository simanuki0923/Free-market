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
            'name.required'  => ':attribute入力必須',
            'name.max'       => ':attribute20文字以内で入力してください',

            'email.required' => ':attribute入力必須',
            'email.email'    => ':attributeを入力してください',
            'email.unique'   => 'その:attributeは既に使用されています',

            'password.required'  => ':attribute入力必須',
            'password.min'       => ':attributeは8文字以上で入力してください',
            'password.confirmed' => 'パスワードと一致しません',

            'password_confirmation.required' => ':attribute入力必須',
            'password_confirmation.min'      => ':attributeは8文字以上で入力してください',
        ];
    }
}
