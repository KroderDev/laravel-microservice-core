<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

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

        return $response;
    }
}