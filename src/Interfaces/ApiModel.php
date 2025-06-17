<?php

namespace Kroderdev\LaravelMicroserviceCore\Interfaces;

use Illuminate\Pagination\LengthAwarePaginator;

interface ApiModel
{
    public static function fromApiResponse(array $data);
    public static function fromPaginatedResponse(array $response): LengthAwarePaginator;
}
