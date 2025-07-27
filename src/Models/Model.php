<?php

namespace Kroderdev\LaravelMicroserviceCore\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Exceptions\ApiGatewayException;
use Kroderdev\LaravelMicroserviceCore\Interfaces\ApiModelContract;
use Kroderdev\LaravelMicroserviceCore\Traits\ApiModelTrait;
use Kroderdev\LaravelMicroserviceCore\Traits\ParsesApiResponse;

abstract class Model extends BaseModel implements ApiModelContract
{
    use ApiModelTrait;
    use ParsesApiResponse;

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

        return '/'.Str::kebab(Str::pluralStudly(class_basename(static::class)));
    }

    /**
     * Get the ApiGateway client instance.
     */
    protected static function client(): ApiGatewayClientInterface
    {
        return app(ApiGatewayClientInterface::class);
    }

    /**
     * Start a new query for the model.
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::class);
    }

    /**
     * Filter models by the given column and value.
     */
    public static function where(string $column, mixed $value): QueryBuilder
    {
        return static::query()->where($column, $value);
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
        $response = static::client()->get(static::endpoint().'/'.$id);
        $data = static::parseResponse($response);

        return static::fromResponse($data);
    }

    /**
     * Find a model by its primary key or throw an exception.
     */
    public static function findOrFail($id, $columns = ['*']): self
    {
        if ($model = static::find($id, $columns)) {
            return $model;
        }

        throw (new ModelNotFoundException())->setModel(static::class, [$id]);
    }

    /**
     * Paginate models from the API.
     */
    public static function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null): LengthAwarePaginator
    {
        $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
        $query = [$pageName => $page, 'per_page' => $perPage];

        // Handle the case where the API returns a 404 for an empty collection
        try {
            $response = static::client()->get(static::endpoint(), $query);
            $data = static::parseResponse($response);
        } catch (ApiGatewayException $e) {
            if ($e->getStatusCode() === 404) {
                return new LengthAwarePaginator([], 0, $perPage);
            }

            throw $e;
        }

        return static::fromPaginatedResponse($data);
    }

    /**
     * Create a model via the API.
     */
    public static function create(array $attributes = []): ?self
    {
        $response = static::client()->post(static::endpoint(), $attributes);
        $data = static::parseResponse($response);

        return static::fromResponse($data);
    }

    /**
     * Update a model via the API.
     *
     * @param int|string $id The ID of the model to update.
     * @param array $attributes The attributes to update.
     * @return bool
     */
    public static function updateById($id, array $attributes): bool
    {
        $method = strtolower(config('microservice.models.update_method', 'put'));
        $response = static::client()->{$method}(static::endpoint().'/'.$id, $attributes);

        if (is_object($response) && method_exists($response, 'successful')) {
            return $response->successful();
        }

        return true;
    }

    /**
     * Update the model via the API.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        if (! $this->exists) {
            return false;
        }

        $this->fill($attributes);

        $method = strtolower(config('microservice.models.update_method', 'put'));
        $response = static::client()->{$method}(static::endpoint().'/'.$this->getKey(), $this->attributesToArray());

        $data = static::parseResponse($response);
        if ($fresh = static::fromResponse($data)) {
            $this->fill($fresh->attributesToArray());
            $this->syncOriginal();
        }

        return true;
    }

    /**
     * Update the model via the API, throwing an exception on failure.
     */
    public function updateOrFail(array $attributes = [], array $options = []): bool
    {
        if (! $this->exists) {
            return false;
        }

        $this->fill($attributes);

        $method = strtolower(config('microservice.models.update_method', 'put'));
        $response = static::client()->{$method}(static::endpoint().'/'.$this->getKey(), $this->attributesToArray());

        if (is_object($response) && method_exists($response, 'successful') && ! $response->successful()) {
            throw new \RuntimeException('Update failed.');
        }

        $data = static::parseResponse($response);
        if ($fresh = static::fromResponse($data)) {
            $this->fill($fresh->attributesToArray());
            $this->syncOriginal();
        }

        return true;
    }

    /**
     * Save the model via the API.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            $method = strtolower(config('microservice.models.update_method', 'put'));
            $response = static::client()->{$method}(static::endpoint().'/'.$this->getKey(), $this->attributesToArray());
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
        $method = strtolower(config('microservice.models.delete_method', 'delete'));
        $response = static::client()->{$method}(static::endpoint().'/'.$this->getKey());

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
        $response = static::client()->get(static::endpoint().'/'.$this->getKey());
        $data = static::parseResponse($response);
        if ($fresh = static::fromResponse($data)) {
            $this->fill($fresh->attributesToArray());
            $this->syncOriginal();
        }

        return $this;
    }
}
