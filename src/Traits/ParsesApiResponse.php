<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

trait ParsesApiResponse
{
    /**
     * Normalize different response types to array.
     *
     * @param mixed $response
     * @return array
     */
    protected static function parseResponse(mixed $response): array
    {
        if (is_null($response)) {
            return [];
        }

        if ($response instanceof HttpResponse) {
            $response->throw();
            $decoded = $response->json();
            return is_array($decoded) ? $decoded : [];
        }

        if ($response instanceof JsonResponse) {
            $decoded = $response->getData(true);
            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($response)) {
            return $response;
        }

        if ($response instanceof Collection) {
            return $response->toArray();
        }

        if (is_string($response)) {
            $decoded = json_decode($response, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                ? $decoded
                : [];
        }

        if (method_exists($response, 'toArray')) {
            try {
                $array = $response->toArray();
                return is_array($array) ? $array : [];
            } catch (\Throwable $e) {
                return [];
            }
        }

        return [];
    }
}
