<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $categoriesInput = $this->input('categories');
        $primaryCategoryId = is_array($categoriesInput)
            ? ($categoriesInput[0] ?? null)
            : $categoriesInput;

        $this->merge([
            'category_id' => $primaryCategoryId,
            'brand'       => $this->input('brand') ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'brand' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'required',
                'string',
                'max:255',
            ],

            'image' => [
                'required',
                'file',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],

            'categories' => [
                'required',
                'array',
                'min:1',
            ],
            'categories.*' => [
                'integer',
                'exists:categories,id',
            ],

            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'condition' => [
                'required',
                'string',
                'max:50',
            ],

            'price' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '商品名は必須です。',
            'name.max'      => '商品名は255文字以内で入力してください。',

            'brand.max'     => 'ブランド名は255文字以内で入力してください。',

            'description.required' => '商品の説明は必須です。',
            'description.max'      => '商品の説明は255文字以内で入力してください。',

            'image.required' => '商品画像は必須です。',
            'image.file'     => '商品画像のアップロードに失敗しました。',
            'image.mimes'    => '商品画像はJPEG/PNG/JPG/WEBP形式を選択してください。',
            'image.max'      => '画像は5MB以下にしてください。',

            'categories.required'   => 'カテゴリーは少なくとも1つ選択してください。',
            'categories.array'      => 'カテゴリーの形式が不正です。',
            'categories.min'        => 'カテゴリーは少なくとも1つ選択してください。',
            'categories.*.integer'  => 'カテゴリーの値が不正です。',
            'categories.*.exists'   => '選択されたカテゴリーが存在しません。',

            'category_id.required'  => 'カテゴリーの指定が不正です。',
            'category_id.integer'   => 'カテゴリーの指定が不正です。',
            'category_id.exists'    => '選択されたカテゴリーが存在しません。',

            'condition.required' => '商品の状態を選択してください。',
            'condition.max'      => '商品の状態は50文字以内で入力してください。',

            'price.required' => '価格は必須です。',
            'price.integer'  => '価格は数値で入力してください。',
            'price.min'      => '価格は0円以上で入力してください。',
        ];
    }
}
