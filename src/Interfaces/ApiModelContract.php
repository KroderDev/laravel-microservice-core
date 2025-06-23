<?php

namespace Kroderdev\LaravelMicroserviceCore\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ApiModelContract
{
    /** Create a model instance from raw API payload */
    public static function fromApiResponse(array $data): static;

    /** Build a collection of models from an array of payloads */
    public static function fromCollection(array $items): Collection;

    /** Extract the model from a standard API response */
    public static function fromResponse(array $response): ?static;

    /** Convert a paginated API response into a paginator of models */
    public static function fromPaginatedResponse(array $response): LengthAwarePaginator;
}
