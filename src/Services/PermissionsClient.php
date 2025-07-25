<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

use Illuminate\Support\Facades\Cache;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;

class PermissionsClient
{
    protected ApiGatewayClientInterface $gateway;

    protected string $endpoint;

    public function __construct(ApiGatewayClientInterface $gateway)
    {
        $this->gateway = $gateway;
        $this->endpoint = rtrim(config('microservice.permissions_endpoint', '/auth/permissions'), '/');
    }

    public function getAccessFor(AccessUserInterface $user): array
    {
        $cacheKey = "user_access:{$user->getAuthIdentifier()}";
        $ttl = config('microservice.permissions_cache_ttl', 60);

        return Cache::remember($cacheKey, $ttl, function () use ($user) {
            $response = $this->gateway->get($this->endpoint.'/'.$user->getAuthIdentifier());

            if ($response->failed()) {
                throw new \RuntimeException('Failed to fetch permissions from API Gateway.');
            }

            return $response->json();
        });
    }
}
