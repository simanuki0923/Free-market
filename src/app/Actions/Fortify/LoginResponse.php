<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

// app/Actions/Fortify/LoginResponse.php
class LoginResponse implements \Laravel\Fortify\Contracts\LoginResponse {
    public function toResponse($request) { return redirect()->route('item'); }
}

