<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認可はミドルウェア(auth)で担保
    }

    public function rules(): array
    {
        return [
            // 郵便番号：必須・8文字(例: 123-4567)・半角数字+ハイフン固定
            'postal_code' => [
                'required',
                'string',
                'size:8',
                'regex:/^\d{3}-\d{4}$/',
            ],
            // 住所(= address1)：必須
            'address1' => ['required','string','max:255'],
            // 任意
            'address2' => ['nullable','string','max:255'],
            'phone'     => ['nullable','string','max:50'],

            // 画面遷移に必要（hidden）
            'item_id'   => ['bail','required','integer','exists:products,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'postal_code' => '郵便番号',
            'address1'    => '住所',
            'address2'    => '建物名',
            'phone'       => '電話番号',
            'item_id'     => '商品ID',
        ];
    }

    public function messages(): array
    {
        return [
            'postal_code.required' => '郵便番号を入力してください。',
            'postal_code.size'     => '郵便番号はハイフン込み8文字（例：123-4567）で入力してください。',
            'postal_code.regex'    => '郵便番号は「123-4567」の形式で入力してください。',
            'address1.required'    => '住所を入力してください。',
            'item_id.required'     => '商品情報が取得できませんでした。',
            'item_id.exists'       => '指定された商品が存在しません。',
        ];
    }
}
