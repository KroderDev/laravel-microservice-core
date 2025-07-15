<?php

namespace Kroderdev\LaravelMicroserviceCore\Models;

use Illuminate\Support\Collection;

class QueryBuilder
{
    protected string $model;

    protected array $filters = [];

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    public function where(string $column, mixed $value): static
    {
        $this->filters[$column] = $value;

        return $this;
    }

    public function get($columns = ['*']): Collection
    {
        $model = $this->model;

        $response = $model::client()->get($model::endpoint(), $this->filters);
        $data = $this->parseResponse($response);

        return $model::fromCollection($data['data'] ?? $data);
    }

    protected function parseResponse($response): array
    {
        if ($response === null) {
            return [];
        }

        if (is_array($response)) {
            return $response;
        }

        if ($response instanceof Collection) {
            return $response->toArray();
        }

        if (method_exists($response, 'json')) {
            return $response->json();
        }

        return (array) $response;
    }
}