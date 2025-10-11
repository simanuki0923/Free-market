<?php

return [

    // 必須などの共通ルールを使う場合は必要に応じて他キーも追記

    'custom' => [
        'email' => [
            'required' => 'メールアドレスを入力してください',
            'email'    => 'メールアドレスが正しくありません。',
        ],
        'password' => [
            'required' => 'パスワードを入力してください',
        ],
    ],

    'attributes' => [
        'email'    => 'メールアドレス',
        'password' => 'パスワード',
    ],
];
