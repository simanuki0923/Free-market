<?php

namespace App\Actions\Fortify;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\FailedLoginResponse as FailedLoginResponseContract;

class FailedLoginResponse implements FailedLoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['message' => 'ログイン情報が登録されていません'], 422);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['auth' => 'ログイン情報が登録されていません']);
    }
}
