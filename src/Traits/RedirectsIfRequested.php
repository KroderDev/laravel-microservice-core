<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

trait RedirectsIfRequested
{
    protected function redirectIfRequested(Request $request, $response)
    {
        if ($request->has('redirect')) {
            $redirectTo = $request->input('redirect');

            return $redirectTo === 'intended'
                ? redirect()->intended()
                : redirect()->to($redirectTo);
        }

        if (Session::has('url.intended')) {
            return redirect()->intended();
        }

        if (! $request->expectsJson() && ! $response instanceof RedirectResponse) {
            $default = Config::get('microservice.gateway_auth.default_redirect', '/');

            return redirect()->to($default);
        }

        return $response;
    }
}