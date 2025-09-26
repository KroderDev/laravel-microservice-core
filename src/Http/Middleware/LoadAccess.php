<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;
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
                'error' => 'unauthorized',
                'message' => 'No authenticated user',
                'status' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user instanceof AccessUserInterface) {
            $oidcConfig = config('microservice.auth.oidc', []);
            $preferGateway = (bool) ($oidcConfig['prefer_gateway_permissions'] ?? false);

            if (($oidcConfig['enabled'] ?? false) && ! $preferGateway) {
                if (! empty($user->getRoleNames()) || ! empty($user->getPermissions())) {
                    return $next($request);
                }
            }

            try {
                $access = app(PermissionsClient::class)->getAccessFor($user);
                $user->loadAccess(
                    $access['roles'] ?? [],
                    $access['permissions'] ?? []
                );
            } catch (\Throwable $e) {
                // Do nothing
            }
        }

        return $next($request);
    }
}
