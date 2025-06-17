<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait ApiModelTrait
{
    public static function fromApiResponse(array $data)
    {
        $instance = new static();
        $instance->fill($data);
        return $instance;
    }

    public static function fromPaginatedResponse(array $response): LengthAwarePaginator
    {
        $items = collect($response['data'] ?? [])
            ->map(fn($item) => static::fromApiResponse($item));

        $meta = $response ?? [];

        return new LengthAwarePaginator(
            $items,
            $meta['total'] ?? $items->count(),
            $meta['per_page'] ?? $items->count(),
            $meta['current_page'] ?? 1,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public static function fromCollection(array $items): Collection
    {
        return collect($items)->map(fn($item) => static::fromApiResponse($item));
    }

    public static function fromResponse(array $response): ?self
    {
        return isset($response['data']) ? static::fromApiResponse($response['data']) : null;
    }


}
