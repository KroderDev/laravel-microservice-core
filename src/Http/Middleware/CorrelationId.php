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
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cfg    = config('microservice-core.correlation');
        $header = $cfg['header'];

        // Use existing header or generate a new UUID
        $id = $request->header($header) ?: Str::uuid()->toString();

        // Set on request and response
        $request->headers->set($header, $id);
        $response = $next($request);
        $response->headers->set($header, $id);

        return $response;
    }
}
