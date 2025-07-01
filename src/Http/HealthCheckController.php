<?php

namespace Kroderdev\LaravelMicroserviceCore\Http;

use Illuminate\Http\JsonResponse;

class HealthCheckController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'laravel' => app()->version(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
