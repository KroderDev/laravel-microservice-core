<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;
use Illuminate\Http\JsonResponse;

class SocialiteController
{
    protected AuthServiceClient $client;

    public function __construct(AuthServiceClient $client)
    {
        $this->client = $client;
    }

    public function redirect(string $provider): RedirectResponse
    {
        $url = $this->client->socialiteRedirect($provider);

        return redirect()->away($url);
    }

    public function callback(Request $request, string $provider)
    {
        $data = $this->client->socialiteCallback(
            $provider,
            (string) $request->query('code'),
            (string) $request->query('state')
        );

        if (isset($data['access_token'])) {
            Auth::guard('gateway')->loginWithToken($data['access_token'], $data['user'] ?? []);
            return redirect()->to('/');
        }

        return response()->json($data, 400);
    }
}