<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LogoutController
{
    public function __invoke(): JsonResponse
    {
        Auth::guard('gateway')->logout();

        return response()->json(['message' => 'logged out']);
    }
}