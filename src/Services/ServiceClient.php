<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Kroderdev\LaravelMicroserviceCore\Contracts\ServiceClientInterface;
use Kroderdev\LaravelMicroserviceCore\Exceptions\ServiceClientException;

class ServiceClient implements ServiceClientInterface
{
    protected string $serviceName;

    protected PendingRequest $http;

    protected ?string $token = null;

    protected function __construct(string $serviceName, PendingRequest $http)
    {
        $this->serviceName = $serviceName;
        $this->http = $http;
    }

    public static function to(string $serviceName): static
    {
        return new static($serviceName, Http::service($serviceName));
    }

    public static function toGateway(): static
    {
        return static::to('gateway');
    }

    public static function direct(string $serviceName): static
    {
        return new static($serviceName, Http::serviceDirect($serviceName));
    }

    public function withToken(string $token): static
    {
        $this->token = $token;
        $correlationHeader = config('microservice.tracing.correlation.header', 'X-Correlation-ID');
        $correlation = app()->bound('request') ? request()->header($correlationHeader) : null;

        $this->http = $this->http->withToken($token)
            ->withHeaders($correlation ? [$correlationHeader => $correlation] : []);

        return $this;
    }

    public function withoutRetry(): static
    {
        $token = $this->token;

        $this->http = Http::serviceDirect($this->serviceName);

        if ($token) {
            $this->http = $this->http->withToken($token);
        }

        return $this;
    }

    public function get(string $uri, array $query = []): mixed
    {
        return $this->handleResponse(
            $this->http->get($uri, $query)
        );
    }

    public function post(string $uri, array $data = []): mixed
    {
        return $this->handleResponse(
            $this->http->post($uri, $data)
        );
    }

    public function put(string $uri, array $data = []): mixed
    {
        return $this->handleResponse(
            $this->http->put($uri, $data)
        );
    }

    public function delete(string $uri): mixed
    {
        return $this->handleResponse(
            $this->http->delete($uri)
        );
    }

    protected function handleResponse(mixed $response): mixed
    {
        if (is_object($response) && method_exists($response, 'failed') && $response->failed()) {
            $data = method_exists($response, 'json') ? $response->json() : [];

            $message = '';
            if (is_array($data)) {
                $message = $data['message'] ?? ($data['error'] ?? '');
            }

            throw new ServiceClientException(
                method_exists($response, 'status') ? $response->status() : 500,
                is_array($data) ? $data : [],
                $message
            );
        }

        if (is_object($response) && method_exists($response, 'json')) {
            return response()->json($response->json(), $response->status());
        }

        return $response;
    }
}
