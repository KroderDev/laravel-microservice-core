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
        'jwt_auth' => 'jwt.auth',         // e.g. 'jwt.auth' or null
        'correlation_id' => 'correlation.id',   // e.g. 'correlation.id' or ''
        'load_access' => 'load.access',
        'role' => 'role',
        'permission' => 'permission',
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
    ],

    /*
    |--------------------------------------------------------------------------
    | Correlation ID Options
    |--------------------------------------------------------------------------
    |
    | Configuration settings related to the propagation of correlation IDs.
    |
    | These settings determine how correlation IDs are managed and passed between
    | different services or components within the microservice architecture.
    | Proper configuration ensures traceability and consistency for distributed requests.
    |
    */
    'correlation' => [
        /**
         * The name of the HTTP header used to transmit the correlation ID for request tracing.
         * This value is used to track and correlate requests across microservices.
         *
         * @var string
         */
        'header' => 'X-Correlation-ID',
        /**
         * The length of the value, used for UUIDs or unique identifiers.
         * Default is 36 characters, which matches the standard UUID string length.
         *
         * @var int
         */
        'length' => 36,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Specifies the base URL of the API Gateway service through which all
    | microservice communication may be routed.
    */
    'api_gateway' => [

        /**
         * API Gateway URL:
         * Base URL of the API Gateway. Can be customized via environment
         * variable for flexibility across different environments.
         */
        'url' => env('API_GATEWAY_URL', 'http://gateway.local'),
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

    /*
    |--------------------------------------------------------------------------
    | Gateway Guard Options
    |--------------------------------------------------------------------------
    |
    | Configure the session-based gateway guard for front-end applications.
    */
    'gateway_guard' => [
        'user_model' => \Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser::class,
        'load_access' => true,
        'me_cache_ttl' => env('GATEWAY_ME_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Auth Controllers
    |--------------------------------------------------------------------------
    |
    | Configuration specific to the built-in authentication controllers.
    | The default_redirect value determines where the user is sent if no
    | redirect parameter or intended URL exists and the request does not
    | expect JSON.
    */
    'gateway_auth' => [
        'default_redirect' => env('GATEWAY_AUTH_DEFAULT_REDIRECT', '/'),
        'allowed_redirect_hosts' => array_filter(
            explode(',', env('GATEWAY_AUTH_ALLOWED_REDIRECT_HOSTS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model HTTP Methods
    |--------------------------------------------------------------------------
    |
    | Configure which HTTP verbs are used when models are updated or deleted
    | through the API gateway. Defaults align with Laravel's expectations.
    */
    'models' => [
        'update_method' => env('MODEL_UPDATE_METHOD', 'put'),
        'delete_method' => env('MODEL_DELETE_METHOD', 'delete'),
    ],
];
