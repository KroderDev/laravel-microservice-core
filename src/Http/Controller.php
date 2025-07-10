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

    /**
     * Convenience wrapper for successful responses.
     */
    protected function successResponse(mixed $data = null, string $message = null, int $statusCode = 200): JsonResponse
    {
        return $this->apiResponse($data, $message, $statusCode, true);
    }

    /**
     * Response for newly created resources.
     */
    protected function createdResponse(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return $this->apiResponse($data, $message, 201, true);
    }

    /**
     * Convenience wrapper for error responses.
     */
    protected function errorResponse(string $message, int $statusCode = 400, mixed $data = null): JsonResponse
    {
        return $this->apiResponse($data, $message, $statusCode, false);
    }

    /**
     * Response used when a resource cannot be found.
     */
    protected function notFoundResponse(string $message = 'Not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Response with no content.
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json([], 204);
    }
}
