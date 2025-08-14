<?php

namespace Kroderdev\LaravelMicroserviceCore\Contracts;

interface ApiGatewayClientInterface
{
    public function get(string $uri, array $query = []): mixed;

    public function post(string $uri, array $data = []): mixed;

    public function put(string $uri, array $data = []): mixed;

    public function delete(string $uri): mixed;
}
