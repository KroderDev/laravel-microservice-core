<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Exceptions\ApiGatewayException;

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
        return $this->handleResponse(
            $this->http->get($uri, $query)
        );
    }

    public function post(string $uri, array $data = [])
    {
        return $this->handleResponse(
            $this->http->post($uri, $data)
        );
    }

    public function put(string $uri, array $data = [])
    {
        return $this->handleResponse(
            $this->http->put($uri, $data)
        );
    }

    public function delete(string $uri)
    {
        return $this->handleResponse(
            $this->http->delete($uri)
        );
    }

    protected function handleResponse($response)
    {
        if (is_object($response) && method_exists($response, 'failed') && $response->failed()) {
            $data = method_exists($response, 'json') ? $response->json() : [];

            throw new ApiGatewayException(
                method_exists($response, 'status') ? $response->status() : 500,
                is_array($data) ? $data : []
            );
        }

        return $response;
    }
}
