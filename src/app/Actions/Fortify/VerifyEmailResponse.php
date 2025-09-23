<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

// app/Actions/Fortify/VerifyEmailResponse.php
class VerifyEmailResponse implements \Laravel\Fortify\Contracts\VerifyEmailResponse {
    public function toResponse($request) { return redirect()->route('profile.edit'); }
}

