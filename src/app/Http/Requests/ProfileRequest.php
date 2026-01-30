<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'icon' => ['nullable', 'file', 'mimes:jpeg,png', 'max:4096'],
            'display_name' => ['required', 'string', 'max:20'],
            'postal_code' => ['required', 'string', 'size:8', 'regex:/^\d{3}-\d{4}$/u'],
            'address_pref_city' => ['required', 'string', 'max:255'],
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
