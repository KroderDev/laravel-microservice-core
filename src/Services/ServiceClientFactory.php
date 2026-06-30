<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

class ServiceClientFactory
{
    public function to(string $serviceName): ServiceClient
    {
        return ServiceClient::to($serviceName);
    }

    public function default(): ServiceClient
    {
        return ServiceClient::toGateway();
    }
}
