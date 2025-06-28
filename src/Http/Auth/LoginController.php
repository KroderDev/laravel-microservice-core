<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController
{
    public function __invoke(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::guard('gateway')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        return response()->json([
            'user' => Auth::guard('gateway')->user(),
        ]);
    }
}