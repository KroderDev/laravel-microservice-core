<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Traits\RedirectsIfRequested;

class LoginController
{
    use RedirectsIfRequested;

    public function __invoke(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::guard('gateway')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $response = response()->json([
            'user' => Auth::guard('gateway')->user(),
        ]);

        return $this->redirectIfRequested($request, $response);
    }
}
