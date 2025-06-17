<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

class ApiGatewayClientFactory
{
    public function default(): ApiGatewayClient
    {
        return ApiGatewayClient::make();
    }

    public function direct(): ApiGatewayClient
    {
        return ApiGatewayClient::direct();
    }

    public function withToken(string $token): ApiGatewayClient
    {
        return ApiGatewayClient::withToken($token);
    }

    public function directWithToken(string $token): ApiGatewayClient
    {
        return ApiGatewayClient::directWithToken($token);
    }
}