<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

use Illuminate\Support\Facades\Cache;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;

class PermissionsClient
{
    protected ApiGatewayClientInterface $gateway;

    public function __construct(ApiGatewayClientInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    public function getAccessFor(ExternalUser $user): array
    {
        $cacheKey = "user_access:{$user->getAuthIdentifier()}";

        return Cache::remember($cacheKey, 60, function () use ($user) {
            $response = $this->gateway->get('/auth/permissions/' . $user->getAuthIdentifier());

            if ($response->failed()) {
                throw new \RuntimeException("Failed to fetch permissions from API Gateway.");
            }

            return $response->json();
        });
    }
}
