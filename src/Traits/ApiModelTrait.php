<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Kroderdev\LaravelMicroserviceCore\Interfaces\ApiModelContract;

trait ApiModelTrait
{
    /**
     * Relation mapping callbacks.
     *
     * Each key should be the relation name and the value either a class name,
     * an array with the class name for collections, or a closure that returns
     * the relation value.
     */
    protected static array $apiRelations = [];

    /**
     * Instantiate a model from raw API data.
     */
    public static function fromApiResponse(array $data): static
    {
        $instance = new static();

        // Handle API relations defined in the model
        foreach (static::$apiRelations as $relation => $cast) {
            if (array_key_exists($relation, $data)) {
                $instance->setRelation(
                    $relation,
                    static::castApiRelation($cast, $data[$relation])
                );
                unset($data[$relation]);
            }
        }

        // Fill the model with the remaining data
        $instance->fill($data);

        // Mark the instance as existing to prevent local database queries
        if (property_exists($instance, 'exists')) {
            $instance->exists = true;
        }

        return $instance;
    }

    /**
     * Cast the given relation value based on the provided definition.
     */
    protected static function castApiRelation(mixed $cast, mixed $value): mixed
    {
        if (is_callable($cast)) {
            return $cast($value);
        }

        if (is_string($cast) && method_exists(static::class, $cast)) {
            return static::$cast($value);
        }

        if (is_array($cast)) {
            $class = $cast[0];

            return collect($value)->map(fn ($item) => static::castApiRelation($class, $item));
        }

        if (is_string($cast) && class_exists($cast)) {
            if (is_subclass_of($cast, ApiModelContract::class)) {
                return $cast::fromApiResponse($value);
            }

            $model = new $cast();
            if (method_exists($model, 'fill')) {
                $model->fill($value);
            }

            return $model;
        }

        return $value;
    }

    /**
     * Create a collection of models from an array of payloads.
     */
    public static function fromCollection(array $items): Collection
    {
        return collect($items)->map(fn ($item) => static::fromApiResponse($item));
    }

    /**
     * Build a model from a typical API response wrapper.
     */
    public static function fromResponse(array $response): ?static
    {
        return isset($response['data']) ? static::fromApiResponse($response['data']) : null;
    }

    /**
     * Convert a paginated API payload into a paginator of models.
     */
    public static function fromPaginatedResponse(array $response): LengthAwarePaginator
    {
        $items = collect($response['data'] ?? [])
            ->map(fn ($item) => static::fromApiResponse($item));

        $meta = $response ?? [];

        $perPage = $meta['per_page'] ?? $items->count();
        if ($perPage <= 0) {
            $perPage = 10;
        }

        return new LengthAwarePaginator(
            $items,
            $meta['total'] ?? $items->count(),
            $perPage,
            $meta['current_page'] ?? 1,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
