<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

trait RedirectsIfRequested
{
    protected function redirectIfRequested(Request $request, $response): RedirectResponse|Response
    {
        $redirectTo = $request->input('redirect');
        return $redirectTo ? redirect()->to($redirectTo) : $response;
    }
}