<?php

namespace Tests\Services;

use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;

class FakeGatewayClient implements ApiGatewayClientInterface
{
    protected array $calls = [];

    public function get(string $uri, array $query = []): mixed
    {
        $this->calls[] = ['method' => 'GET', 'uri' => $uri, 'query' => $query];

        return collect(['fake' => true, 'uri' => $uri, 'query' => $query]);
    }

    public function post(string $uri, array $data = []): mixed
    {
        $this->calls[] = ['method' => 'POST', 'uri' => $uri, 'data' => $data];

        return collect(['fake' => true, 'uri' => $uri, 'data' => $data]);
    }

    public function put(string $uri, array $data = []): mixed
    {
        $this->calls[] = ['method' => 'PUT', 'uri' => $uri, 'data' => $data];

        return collect(['fake' => true, 'uri' => $uri, 'data' => $data]);
    }

    public function delete(string $uri): mixed
    {
        $this->calls[] = ['method' => 'DELETE', 'uri' => $uri];

        return collect(['fake' => true, 'uri' => $uri]);
    }

    public function getCalls(): array
    {
        return $this->calls;
    }
}
