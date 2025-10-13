<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:credit_card,convenience_store'],
            'ship_to'       => ['required', 'in:profile'],
            'postal_code'   => ['required', 'regex:/^\d{3}-\d{4}$/', 'size:8'],
            'address1'      => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => '支払い方法を選択してください。',
            'payment_method.in'       => '支払い方法の選択が不正です。',
            'ship_to.required'        => '配送先を選択してください。',
            'ship_to.in'              => '配送先の指定が不正です。',
            'postal_code.required'    => '郵便番号を入力してください。',
            'postal_code.regex'       => '郵便番号は「123-4567」の形式で入力してください。',
            'postal_code.size'        => '郵便番号はハイフン含め8文字で入力してください。',
            'address1.required'       => '住所を入力してください。',
            'address1.max'            => '住所は255文字以内で入力してください。',
        ];
    }
}
