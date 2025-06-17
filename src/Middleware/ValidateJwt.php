<?php

namespace Kroderdev\LaravelMicroserviceCore\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;
use Symfony\Component\HttpFoundation\Response;

class ValidateJwt
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = config('microservice.auth.header', 'Authorization');
        $authHeader = $request->header($header);

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($authHeader, 7);

        try {
            $publicKey = file_get_contents(config('microservice.auth.jwt_public_key'));

            $decoded = JWT::decode($token, new Key($publicKey, config('microservice.auth.jwt_algorithm')));

            // Auth from JWT
            $user = new ExternalUser((array) $decoded);

            // Get Permissions, permits tolerance
            try {
                $access = app(PermissionsClient::class)->getAccessFor($user);
            } catch (\Throwable $e) {
                $access = [
                    'roles' => [],
                    'permissions' => [],
                ];
            }

            $user->loadAccess(
                $access['roles'] ?? [],
                $access['permissions'] ?? []
            );

            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid token', 'message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
