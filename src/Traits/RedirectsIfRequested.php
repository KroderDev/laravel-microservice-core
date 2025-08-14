<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

trait RedirectsIfRequested
{
    protected function redirectIfRequested(Request $request, $response)
    {
        if ($request->has('redirect')) {
            $redirectTo = $request->input('redirect');
            if ($redirectTo === 'intended') {
                return redirect()->intended();
            }

            if ($this->isAllowedRedirect($redirectTo)) {
                return redirect()->to($redirectTo);
            }
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

    protected function isAllowedRedirect(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return str_starts_with($url, '/');
        }

        $allowed = Config::get('microservice.gateway_auth.allowed_redirect_hosts', []);

        return in_array($host, $allowed, true);
    }
}
