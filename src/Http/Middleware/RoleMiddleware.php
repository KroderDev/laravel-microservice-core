<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check that the authenticated User has the given role.
 * Usage: ->middleware(['role:admin'])
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request, string): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole($role)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'forbidden',
                    'message' => "User does not have required role: {$role}",
                    'status'  => Response::HTTP_FORBIDDEN,
                ], Response::HTTP_FORBIDDEN);
            }
            abort(Response::HTTP_FORBIDDEN, "User does not have required role: {$role}");
        }
        return $next($request);
    }
}