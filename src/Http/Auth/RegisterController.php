<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;

class RegisterController
{
    protected AuthServiceClient $client;

    public function __construct(AuthServiceClient $client)
    {
        $this->client = $client;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->all();
        $response = $this->client->register($data);

        if (isset($response['access_token'])) {
            Auth::guard('gateway')->loginWithToken($response['access_token'], $response['user'] ?? []);
        }

        return response()->json($response);
    }
}