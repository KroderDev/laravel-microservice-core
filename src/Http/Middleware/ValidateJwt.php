<?php

namespace Kroderdev\LaravelMicroserviceCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Services\JwtValidator;
use Symfony\Component\HttpFoundation\Response;

class ValidateJwt
{
    public function __construct(protected JwtValidator $validator)
    {
    }

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
            $decoded = $this->validator->decode($token);
            $claims = $this->toArray($decoded);

            $attributes = $this->buildUserAttributes($claims);

            $user = new ExternalUser($attributes);
            $user->setClaims($claims);

            $roles = $this->extractRoles($claims);
            $permissions = $this->extractPermissions($claims, $roles);

            $user->loadAccess($roles, $permissions);

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

    protected function toArray(mixed $value): array
    {
        if ($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if (! is_array($value)) {
            return [];
        }

        foreach ($value as $key => $item) {
            if ($item instanceof \stdClass || is_array($item)) {
                $value[$key] = $this->toArray($item);
            }
        }

        return $value;
    }

    protected function buildUserAttributes(array $claims): array
    {
        $attributes = $claims;
        $identifierClaim = config('microservice.auth.user_identifier_claim', 'id');

        if (! isset($attributes[$identifierClaim]) && isset($attributes['sub'])) {
            $attributes[$identifierClaim] = $attributes['sub'];
        }

        if ($identifierClaim === 'id' && isset($attributes['sub'])) {
            $attributes['id'] = $attributes['sub'];
        }

        return $attributes;
    }

    protected function extractRoles(array $claims): array
    {
        $roles = $this->resolveClaim($claims, config('microservice.auth.roles_claim'));

        if (! empty($roles)) {
            return $roles;
        }

        $primaryRoles = $this->resolveClaim(
            $claims,
            config('microservice.auth.oidc.primary_roles_claim', 'realm_access.roles')
        );

        return $primaryRoles;
    }

    protected function extractPermissions(array $claims, array $roles): array
    {
        $permissions = $this->resolveClaim($claims, config('microservice.auth.permissions_claim'));

        if (! empty($permissions)) {
            return $permissions;
        }

        $oidcConfig = config('microservice.auth.oidc', []);
        $permissions = [];

        if (($oidcConfig['map_client_roles_to_permissions'] ?? true)) {
            $clientClaim = $this->clientRolesPath($oidcConfig);
            $paths = array_unique(array_filter([
                $clientClaim,
                'resource_access.*.roles',
            ]));
            $permissions = $this->resolveClaim($claims, $paths);

            if (empty($permissions) && isset($claims['resource_access']) && is_array($claims['resource_access'])) {
                $permissions = $this->collectResourceRoles($claims['resource_access'], $oidcConfig['client_id'] ?? null);
            }
        }

        if (empty($permissions) && ($oidcConfig['map_primary_roles_to_permissions'] ?? false)) {
            $permissions = $this->resolveClaim(
                $claims,
                $oidcConfig['primary_roles_claim'] ?? 'realm_access.roles'
            );
        }

        if (empty($permissions)) {
            return $roles;
        }

        return $permissions;
    }

    protected function collectResourceRoles(array $resourceAccess, ?string $clientId): array
    {
        $roles = [];

        if ($clientId && isset($resourceAccess[$clientId]['roles'])) {
            $roles = $resourceAccess[$clientId]['roles'];
        } else {
            foreach ($resourceAccess as $data) {
                if (! is_array($data) || empty($data['roles'])) {
                    continue;
                }
                $roles = array_merge($roles, (array) $data['roles']);
            }
        }

        return $this->normalizeAccessValues($roles);
    }

    protected function clientRolesPath(array $config): ?string
    {
        $path = $config['client_roles_claim'] ?? 'resource_access.*.roles';
        $clientId = $config['client_id'] ?? null;

        if ($clientId) {
            if (str_contains($path, '%s')) {
                return sprintf($path, $clientId);
            }

            if (str_contains($path, '{client}')) {
                return str_replace('{client}', $clientId, $path);
            }
        }

        if (! $clientId && (str_contains($path, '%s') || str_contains($path, '{client}'))) {
            return 'resource_access.*.roles';
        }

        return $path;
    }

    protected function resolveClaim(array $claims, string|array|null $path): array
    {
        if (empty($path)) {
            return [];
        }

        $paths = (array) $path;
        $values = [];

        foreach ($paths as $claimPath) {
            if (! $claimPath) {
                continue;
            }

            $value = Arr::get($claims, $claimPath);
            $values = array_merge($values, $this->normalizeAccessValues($value));
        }

        $values = array_values(array_unique($values));

        return $values;
    }

    protected function normalizeAccessValues(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            $value = [$value];
        }

        $items = [];

        array_walk_recursive($value, function ($item) use (&$items) {
            if (is_string($item) && $item !== '') {
                $items[] = $item;
            }
        });

        return $items;
    }
}
