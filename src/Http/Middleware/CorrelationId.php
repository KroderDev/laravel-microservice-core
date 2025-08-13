<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generate or propagate a unique X-Correlation-ID header.
 */
class CorrelationId
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cfg = config('microservice.correlation');
        $header = $cfg['header'];
        $length = (int) ($cfg['length'] ?? 36);

        // Use existing header or generate a new ID of the configured length
        $id = $request->header($header);
        if (! $id) {
            $id = Str::random($length);
        }

        // Set on request and response
        $request->headers->set($header, $id);
        $response = $next($request);
        $response->headers->set($header, $id);

        return $response;
    }
}
