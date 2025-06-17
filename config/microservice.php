<?php

return [
    /**
     * --------------------------------------------------------------------------
     * Authentication Configuration
     * --------------------------------------------------------------------------
     *
     * Defines settings related to JWT-based authentication, including the
     * public key for token verification, the signing algorithm, and the
     * HTTP header expected to carry the token.
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
         * Authorization Header:
         * The HTTP header from which to extract the JWT token.
         * Common values are 'Authorization' or 'X-Access-Token'.
         */
        'header' => 'Authorization',
    ],

    /**
     * --------------------------------------------------------------------------
     * API Gateway Configuration
     * --------------------------------------------------------------------------
     *
     * Specifies the base URL of the API Gateway service through which all
     * microservice communication may be routed.
     */

    'api_gateway' => [

        /**
         * API Gateway URL:
         * Base URL of the API Gateway. Can be customized via environment
         * variable for flexibility across different environments.
         */
        'url' => env('API_GATEWAY_URL', 'http://gateway.local'),
    ],
];
