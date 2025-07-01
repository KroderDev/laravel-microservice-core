<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
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
        $prefix = config('microservice.auth.prefix', 'Bearer');
        $authHeader = $request->header($header);

        if (! $authHeader || ! str_starts_with($authHeader, (string) $prefix.' ')) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = substr($authHeader, strlen((string) $prefix) + 1);

        try {
            $publicKey = Cache::remember('jwt_public_key', config('microservice.auth.jwt_cache_ttl', 3600), function () {
                return file_get_contents(config('microservice.auth.jwt_public_key'));
            });

            $decoded = JWT::decode($token, new Key($publicKey, config('microservice.auth.jwt_algorithm')));

            // Auth from JWT
            $user = new ExternalUser(['sub' => $decoded->sub]);
            $user->loadAccess(
                $decoded->roles ?? [],
                $decoded->permissions ?? []
            );
            Auth::setUser($user);
            $request->setUserResolver(fn () => $user);

        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Invalid token', 'message' => $e->getMessage()], Response::HTTP_UNAUTHORIZED);
            }

            return response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
