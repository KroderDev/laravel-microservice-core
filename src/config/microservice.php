<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | Define the alias under which each middleware will be registered.
    | Set to null or empty string to disable.
    |
    */
    'middleware_aliases' => [
        'jwt_auth' => 'jwt.auth',
        'correlation_id' => 'correlation.id',
        'load_access' => 'load.access',
        'role' => 'role',
        'permission' => 'permission',
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Registry
    |--------------------------------------------------------------------------
    |
    | Define all services your application communicates with. Each entry
    | specifies the base URL, timeout, and retry behavior. The "gateway"
    | entry provides backward compatibility for the centralized API gateway
    | pattern, but services can also communicate directly with each other.
    |
    | Use the Http::service('name') macro or ServiceClient::to('name')
    | factory to resolve a service at runtime.
    |
    */
    'services' => [
        'default' => env('DEFAULT_SERVICE_URL', env('API_GATEWAY_URL', 'http://gateway.local')),

        'registry' => [

            'gateway' => [
                'url' => env('API_GATEWAY_URL', 'http://gateway.local'),
                'timeout' => 5,
                'retries' => 2,
            ],

        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Defines settings related to JWT-based authentication, including the
    | public key for token verification, the signing algorithm, and the
    | HTTP header expected to carry the token.
    */
    'auth' => [

        /**
         * JWT Public Key:
         * Path or URL to the RSA public key used to verify incoming JWT tokens.
         * Can be a local file path or a remote URL, typically stored securely in the environment file.
         */
        'jwt_public_key' => env('JWT_PUBLIC_KEY_PATH'),

        /**
         * JWT Algorithm:
         * The cryptographic algorithm used for verifying JWT signatures.
         * Supported algorithms (based on firebase/php-jwt):
         *   - HS256, HS384, HS512 (HMAC using SHA-256/384/512)
         *   - RS256, RS384, RS512 (RSA using SHA-256/384/512)
         *   - ES256, ES384, ES512 (ECDSA using SHA-256/384/512)
         *   - EdDSA (Ed25519 signature)
         */
        'jwt_algorithm' => env('JWT_ALGORITHM', 'RS256'),

        /**
         * The time-to-live (TTL) in seconds for caching JWT keys.
         * Determines how long the JWT key will be stored in cache before it expires.
         * Default is 3600 seconds (1 hour).
         */
        'jwt_cache_ttl' => env('JWT_CACHE_TTL', 3600),

        /**
         * Authorization Header:
         * The HTTP header from which to extract the JWT token.
         * Common values are 'Authorization' or 'X-Access-Token'.
         */
        'header' => 'Authorization',

        /**
         * JWT Prefix:
         * The prefix expected before the JWT token in the header (e.g., "Bearer").
         */
        'prefix' => 'Bearer',

        /**
         * JWT User Identifier Claim:
         * Determines which claim should be used as the unique identifier for the
         * authenticated user. Defaults to "id" for backwards compatibility. Set
         * to "sub" (or any other claim) when mirroring OpenID Connect providers
         * such as Keycloak.
         */
        'user_identifier_claim' => env('JWT_USER_IDENTIFIER_CLAIM', 'id'),

        /**
         * JWT Roles Claim:
         * Optional dot-notation path to the claim that contains role names.
         * When null, sensible defaults or provider-specific mappings are used.
         */
        'roles_claim' => env('JWT_ROLES_CLAIM'),

        /**
         * JWT Permissions Claim:
         * Optional dot-notation path to the claim that contains permission
         * names. When null, the middleware falls back to provider-specific
         * mappings.
         */
        'permissions_claim' => env('JWT_PERMISSIONS_CLAIM'),

        /**
         * OpenID Connect Integration Options:
         * Enable JWKS support and claim mappings when validating tokens issued
         * by providers such as Keycloak. The JWKS URL is recommended to
         * automatically handle key rotation. client_roles_claim accepts
         * placeholders ("%s" or "{client}") that will be replaced with the
         * configured client_id.
         */
        'oidc' => [
            'enabled' => env('OIDC_ENABLED', false),
            'jwks_url' => env('OIDC_JWKS_URL'),
            'client_id' => env('OIDC_CLIENT_ID'),
            'primary_roles_claim' => env('OIDC_PRIMARY_ROLES_CLAIM', 'realm_access.roles'),
            'client_roles_claim' => env('OIDC_CLIENT_ROLES_CLAIM', 'resource_access.{client}.roles'),
            'map_client_roles_to_permissions' => env('OIDC_MAP_CLIENT_ROLES_TO_PERMISSIONS', true),
            'map_primary_roles_to_permissions' => env('OIDC_MAP_PRIMARY_ROLES_TO_PERMISSIONS', false),
            'prefer_gateway_permissions' => env('OIDC_PREFER_GATEWAY_PERMISSIONS', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for distributed request tracing. Supports correlation IDs
    | today, with planned support for W3C Trace Context and OpenTelemetry.
    |
    */
    'tracing' => [

        /**
         * Tracing driver: "correlation_id", "w3c", "otel", or "none".
         * Future releases will add W3C Trace Context and OpenTelemetry support.
         */
        'driver' => env('TRACING_DRIVER', 'correlation_id'),

        'correlation' => [
            /**
             * The name of the HTTP header used to transmit the correlation ID for request tracing.
             */
            'header' => 'X-Correlation-ID',

            /**
             * The length of the correlation ID value.
             * Default is 36 characters, matching the standard UUID string length.
             */
            'length' => 36,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Endpoint
    |--------------------------------------------------------------------------
    |
    | Enable or disable registration of the default /health route.
    |
    */
    'health' => [
        'enabled' => env('HEALTH_ENDPOINT_ENABLED', true),
        'path' => '/health',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions Cache
    |--------------------------------------------------------------------------
    |
    | Defines how long (in seconds) fetched roles and permissions are cached
    | for an authenticated user. Adjust via the PERMISSIONS_CACHE_TTL
    | environment variable to control cache duration.
    */
    'permissions_cache_ttl' => env('PERMISSIONS_CACHE_TTL', 60),
    'permissions_endpoint' => env('PERMISSIONS_ENDPOINT', '/auth/permissions'),

];
