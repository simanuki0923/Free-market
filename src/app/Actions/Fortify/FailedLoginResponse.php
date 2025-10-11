<?php

namespace App\Actions\Fortify;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\FailedLoginResponse as FailedLoginResponseContract;

class FailedLoginResponse implements FailedLoginResponseContract
{
    public function toResponse($request)
    {
        // API(JSON) の場合
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => 'ログイン情報が登録されていません'], 422);
        }

        // Web(セッション) の場合：'auth' キーに全体エラーを積む
        return back()
            ->withInput($request->only('email'))
            ->withErrors(['auth' => 'ログイン情報が登録されていません']);
    }
}
