<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fetch roles & permissions for the current User from the ApiGateway.
 */
class LoadAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // If no user, skip
        if (! $user) {
            return response()->json([
                'error'   => 'unauthorized',
                'message' => 'No authenticated user',
                'status'  => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $access = app(PermissionsClient::class)->getAccessFor($user);
            $user->loadAccess(
                $access['roles'] ?? [],
                $access['permissions'] ?? []
            );
        } catch (\Throwable $e) {
            // Do nothing
            // $user->loadAccess([], []);
        }

        return $next($request);
    }
}