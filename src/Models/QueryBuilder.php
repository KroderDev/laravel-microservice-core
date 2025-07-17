<?php

namespace Kroderdev\LaravelMicroserviceCore\Models;

use Illuminate\Support\Collection;
use Kroderdev\LaravelMicroserviceCore\Traits\ParsesApiResponse;

class QueryBuilder
{
    use ParsesApiResponse;

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
}
