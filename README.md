# laravel-microservice-core

[![Packagist Version](https://img.shields.io/packagist/v/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core) [![Downloads](https://img.shields.io/packagist/dt/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core) [![License](https://img.shields.io/packagist/l/kroderdev/laravel-microservice-core.svg)](LICENSE)

A toolkit to leverage **Laravel 12** as a performant and configurable microservice framework within distributed architectures.  

## Installation

Install directly from Packagist via Composer:

1. Require the package via Composer:

```bash
composer require kroderdev/laravel-microservice-core
```

>[!NOTE]
> To install the dev branch, append `:dev-main` to the package name.

### Publish Configuration

After installation, publish the configuration file to your Laravel project:


```bash
php artisan vendor:publish --provider="Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider"
```

You can now customize the settings to match your microservice environment.

---

## Middlewares

This package provides a set of middleware designed to enhance your Laravel microservice with common cross-cutting concerns. You can enable, disable or rename each middleware by editing the `middleware_aliases` section in `config/microservice.php`.

```php
// config/microservice.php

'middleware_aliases' => [
    'jwt_auth'       => 'jwt.auth',        // alias for JWT Authentication middleware
    'correlation_id' => 'correlation.id',  // alias for Correlation ID middleware
    'disabled_mw'    => '',                // empty string disables a middleware
],
```

### JWT Authentication

**Alias**: the string you assign to `jwt_auth` in `middleware_aliases`, e.g. `jwt.auth`.
**Class**: `Kroderdev\LaravelMicroserviceCore\Http\Middleware\ValidateJwt`

#### Description

Validates the presence and integrity of a JSON Web Token (JWT) in the `Authorization` header of incoming requests. On success, the decoded payload is attached to the request for downstream use; on failure, a 401 response is returned.

#### Configuration (`config/microservice.php`)

```php
'auth' => [
    'jwt_public_key' => env('JWT_PUBLIC_KEY_PATH'),
    'jwt_algorithm' => env('JWT_ALGORITHM', 'RS256'),
    'header' => 'Authorization',
    'prefix' => 'Bearer', 
],
```

#### Usage

Register the middleware on routes or globally:

```php
// In routes/api.php
Route::middleware(['jwt.auth'])->group(function () {
    // protected routes…
});
```

#### Example Response on Failure
```json
// Missing or invalid token
{
  "error": "unauthorized",
  "message": "Invalid or expired token"
}
```

---

### Correlation ID

**Alias**: the string you assign to `correlation_id` in `middleware_aliases`, e.g. `correlation.id`.
**Class**: `Kroderdev\LaravelMicroserviceCore\Http\Middleware\CorrelationId`

#### Description

Generates or propagates a unique `X-Correlation-ID` header for each request, enabling end-to-end tracing across distributed systems. If the incoming request already has the header, it will be reused; otherwise, a new UUID is generated. The header is added to both the request and the response.

#### Configuration (`config/microservice.php`)

```php
'correlation' => [
    'header' => 'X-Correlation-ID',
    'length' => 36,
],
```

#### Usage

Automatically prepended to the `api` middleware group (when enabled), or apply explicitly:

```php
// In routes/api.php
Route::middleware(['correlation.id'])->group(function () {
    // traced routes…
});
```

#### Example Header

```
X-Correlation-ID: 123e4567-e89b-12d3-a456-426614174000
```

---

## Public Release and Future Goals

This repository is brand new, and I’m excited to develop it further! My plan is to continuously strengthen the core, add more middleware modules, expand test coverage, and refine configuration options.  

Your feedback, issues or pull requests are very welcome—together we can shape this toolkit into a reliable, production-ready solution for Laravel microservices. I hope you find it helpful and look forward to your contributions!
