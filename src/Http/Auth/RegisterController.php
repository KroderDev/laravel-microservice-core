<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;
use Kroderdev\LaravelMicroserviceCore\Traits\RedirectsIfRequested;

class RegisterController
{
    use RedirectsIfRequested;

    protected AuthServiceClient $client;

    public function __construct(AuthServiceClient $client)
    {
        $this->client = $client;
    }

    public function __invoke(Request $request)
    {
        $data = $request->all();
        $response = $this->client->register($data);

        if (isset($response['access_token'])) {
            Auth::guard('gateway')->loginWithToken($response['access_token'], $response['user'] ?? []);
        }

        $json = response()->json($response);

        return $this->redirectIfRequested($request, $json);
    }
}
