<?php

namespace Kroderdev\LaravelMicroserviceCore\Http;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Return a standardized JSON API response.
     *
     * @param mixed $data         The response data (array, object, etc).
     * @param string|null $message  Optional message for context or status.
     * @param int $statusCode     HTTP status code (default: 200 OK).
     * @param bool $success       Whether the request was successful.
     * @return JsonResponse
     */
    protected function apiResponse(mixed $data = null, string $message = null, int $statusCode = 200, bool $success = true): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }
}