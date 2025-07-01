<?php

namespace Kroderdev\LaravelMicroserviceCore\Contracts;

interface ApiGatewayClientInterface
{
    public function get(string $uri, array $query = []);

    public function post(string $uri, array $data = []);

    public function put(string $uri, array $data = []);

    public function delete(string $uri);
}
