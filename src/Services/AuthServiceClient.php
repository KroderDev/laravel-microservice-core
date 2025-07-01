<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AuthServiceClient
{
    public function login(array $credentials): array
    {
        $response = Http::apiGatewayDirect()->post('/auth/login', $credentials);

        return $response->json() ?: [];
    }

    public function register(array $data): array
    {
        $response = Http::apiGatewayDirect()->post('/auth/register', $data);

        return $response->json() ?: [];
    }

    public function refresh(string $token): array
    {
        return Http::apiGatewayWithToken($token)->post('/auth/refresh')->throw()->json();
    }

    public function me(string $token): array
    {
        $ttl = (int) config('microservice.gateway_guard.me_cache_ttl', 300);
        $expires = $this->tokenExpiresAt($token);
        if ($expires) {
            $ttl = min($ttl, max(0, $expires - time()));
        }

        $cacheKey = 'auth_me_'.md5($token);

        return Cache::remember($cacheKey, $ttl, function () use ($token) {
            return Http::apiGatewayWithToken($token)->post('/auth/me')->json();
        });
    }

    protected function tokenExpiresAt(string $token): ?int
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) < 2) {
                return null;
            }
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            return isset($payload['exp']) ? (int) $payload['exp'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function socialiteRedirect(string $provider): string
    {
        $response = Http::apiGateway()->post("/socialite/{$provider}/redirect")->json();

        return $response['url'] ?? '';
    }

    public function socialiteCallback(string $provider, string $code, string $state): array
    {
        return Http::apiGatewayDirect()->get("/socialite/{$provider}/callback", compact('code', 'state'))->json();
    }
}
