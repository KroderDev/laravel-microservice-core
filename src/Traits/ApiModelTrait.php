<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ApiModelTrait
{
    /**
     * Instantiate a model from raw API data.
     */
    public static function fromApiResponse(array $data): static
    {
        $instance = new static();
        $instance->fill($data);

        return $instance;
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

        return new LengthAwarePaginator(
            $items,
            $meta['total'] ?? $items->count(),
            $meta['per_page'] ?? $items->count(),
            $meta['current_page'] ?? 1,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
