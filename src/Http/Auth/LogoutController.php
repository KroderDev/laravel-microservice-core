<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Traits\RedirectsIfRequested;

class LogoutController
{
    use RedirectsIfRequested;

    public function __invoke(Request $request)
    {
        Auth::guard('gateway')->logout();

        $response = response()->json(['message' => 'logged out']);

        return $this->redirectIfRequested($request, $response);
    }
}
