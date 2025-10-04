<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認証はミドルウェア側で担保
    }

    public function rules(): array
    {
        return [
            // プロフィール画：拡張子 .jpeg / .png（※.jpgは不可）+ 最大4MB
            'icon' => ['nullable', 'file', 'mimes:jpeg,png', 'max:4096'],

            // ユーザー名：必須、20文字以内
            'display_name' => ['required', 'string', 'max:20'],

            // 郵便番号：必須、ハイフンありの8文字（例：123-4567）
            'postal_code' => ['required', 'string', 'size:8', 'regex:/^\d{3}-\d{4}$/u'],

            // 住所：必須（address_pref_cityを正）
            'address_pref_city' => ['required', 'string', 'max:255'],

            // 任意：マンション名
            'building_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'icon.mimes' => 'プロフィール画像は .jpeg もしくは .png を選択してください。',
            'icon.max'   => 'プロフィール画像は 4MB 以下にしてください。',

            'display_name.required' => 'ユーザー名は必須です。',
            'display_name.max'      => 'ユーザー名は20文字以内で入力してください。',

            'postal_code.required' => '郵便番号は必須です。',
            'postal_code.size'     => '郵便番号はハイフンを含む8文字（例：123-4567）で入力してください。',
            'postal_code.regex'    => '郵便番号は 123-4567 の形式で入力してください。',

            'address_pref_city.required' => '住所は必須です。',
            'address_pref_city.max'      => '住所は255文字以内で入力してください。',
            'building_name.max'          => 'マンション名は255文字以内で入力してください。',
        ];
    }

    /**
     * 拡張子を厳密に .jpeg / .png のみに制限（.jpg を弾く）
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->hasFile('icon')) {
                $ext = strtolower((string) $this->file('icon')->getClientOriginalExtension());
                if (!in_array($ext, ['jpeg', 'png'], true)) {
                    $v->errors()->add('icon', 'プロフィール画像の拡張子は .jpeg または .png のみ許可されています。');
                }
            }
        });
    }
}
