<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;

class ApiGatewayClient implements ApiGatewayClientInterface
{
    protected PendingRequest $http;

    protected function __construct(PendingRequest $http)
    {
        $this->http = $http;
    }

    public static function make(): static
    {
        return new static(Http::apiGateway());
    }

    public static function direct(): static
    {
        return new static(Http::apiGatewayDirect());
    }

    public static function withToken(string $token): static
    {
        return new static(Http::apiGatewayWithToken($token));
    }

    public static function directWithToken(string $token): static
    {
        return new static(Http::apiGatewayDirectWithToken($token));
    }

    public function get(string $uri, array $query = [])
    {
        return $this->http->get($uri, $query);
    }

    public function post(string $uri, array $data = [])
    {
        return $this->http->post($uri, $data);
    }

    public function put(string $uri, array $data = [])
    {
        return $this->http->put($uri, $data);
    }

    public function delete(string $uri)
    {
        return $this->http->delete($uri);
    }
}