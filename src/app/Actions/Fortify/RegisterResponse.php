<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements \Laravel\Fortify\Contracts\RegisterResponse {
    public function toResponse($request) { return redirect()->route('verification.notice'); }
}
