<?php

namespace Kroderdev\LaravelMicroserviceCore\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Interfaces\ApiModelContract;
use Kroderdev\LaravelMicroserviceCore\Traits\ApiModelTrait;

abstract class Model extends BaseModel implements ApiModelContract
{
    use ApiModelTrait;

    /**
     * Endpoint for this model. Defaults to plural kebab of the class name.
     */
    protected static string $endpoint = '';

    /**
     * Return the endpoint for the model.
     */
    protected static function endpoint(): string
    {
        if (static::$endpoint !== '') {
            return static::$endpoint;
        }

        return '/' . Str::kebab(Str::pluralStudly(class_basename(static::class)));
    }

    /**
     * Get the ApiGateway client instance.
     */
    protected static function client(): ApiGatewayClientInterface
    {
        return app(ApiGatewayClientInterface::class);
    }

    /**
     * Get all models.
     */
    public static function all($columns = ['*']): Collection
    {
        $response = static::client()->get(static::endpoint());
        $data = static::parseResponse($response);

        return static::fromCollection($data['data'] ?? $data);
    }

    /**
     * Find a model by its primary key.
     */
    public static function find($id, $columns = ['*']): ?self
    {
        $response = static::client()->get(static::endpoint() . '/' . $id);
        $data = static::parseResponse($response);

        return static::fromResponse($data);
    }

    /**
     * Paginate models from the API.
     */
    public static function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginator
    {
        $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
        $query = [$pageName => $page, 'per_page' => $perPage];
        $response = static::client()->get(static::endpoint(), $query);
        $data = static::parseResponse($response);

        return static::fromPaginatedResponse($data);
    }

    /**
     * Create a model via the API.
     */
    public static function create(array $attributes = []): self|null
    {
        $response = static::client()->post(static::endpoint(), $attributes);
        $data = static::parseResponse($response);

        return static::fromResponse($data);
    }

    /**
     * Save the model via the API.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            $response = static::client()->put(static::endpoint() . '/' . $this->getKey(), $this->attributesToArray());
        } else {
            $response = static::client()->post(static::endpoint(), $this->attributesToArray());
        }

        $data = static::parseResponse($response);
        if ($fresh = static::fromResponse($data)) {
            $this->fill($fresh->attributesToArray());
            $this->exists = true;
            $this->syncOriginal();
        }

        return true;
    }

    /**
     * Delete the model via the API.
     */
    public function delete(): bool
    {
        $response = static::client()->delete(static::endpoint() . '/' . $this->getKey());

        if (is_object($response) && method_exists($response, 'successful')) {
            return $response->successful();
        }

        return true;
    }

    /**
     * Refresh the model from the API.
     */
    public function refresh(): static
    {
        $response = static::client()->get(static::endpoint() . '/' . $this->getKey());
        $data = static::parseResponse($response);
        if ($fresh = static::fromResponse($data)) {
            $this->fill($fresh->attributesToArray());
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * Normalize different response types to array.
     */
    protected static function parseResponse($response): array
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