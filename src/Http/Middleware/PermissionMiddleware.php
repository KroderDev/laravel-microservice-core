<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check that the authenticated ExternalUser has the given permission.
 * Usage: ->middleware(['permission:posts.create'])
 */
class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request, string): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();
        if (! $user || ! $user->hasPermissionTo($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'forbidden',
                    'message' => "User does not have required permission: {$permission}",
                    'status' => Response::HTTP_FORBIDDEN,
                ], Response::HTTP_FORBIDDEN);
            }
            abort(Response::HTTP_FORBIDDEN, "User does not have required permission: {$permission}");
        }

        return $next($request);
    }
}
