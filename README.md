# laravel-microservice-core

[![Packagist Version](https://img.shields.io/packagist/v/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core) [![Downloads](https://img.shields.io/packagist/dt/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core) [![License](https://img.shields.io/packagist/l/kroderdev/laravel-microservice-core.svg)](LICENSE)

A toolkit that turns **Laravel 12** into a lightweight base for distributed microservices. 

## Overview

This package provides a robust foundation for building Laravel-based microservices, focusing on stateless authentication, authorization, and distributed system best practices. Out of the box, you’ll find:

- **JWT Authentication Middleware:**  
  Securely validates JWT tokens and hydrates the authenticated user for each request.

- **Role & Permission Middleware:**  
  Restrict access to routes based on user roles or permissions, with support for both JWT-embedded and dynamically loaded access control.

- **LoadAccess Middleware:**
  Optionally fetches the latest roles and permissions for the authenticated user from a centralized permission service or API Gateway.

- **Gateway Guard:**
  Session-based guard that refreshes JWT tokens via the API Gateway and loads permissions automatically.

- **Correlation ID Middleware:**  
  Automatically generates or propagates a unique correlation ID for every request, enabling end-to-end tracing across distributed systems.

- **Configurable Middleware Aliases & Groups:**  
  Easily enable, disable, or rename middleware via configuration, and use convenient groups like `microservice.auth` group for full authentication and authorization in one step.

- **HTTP Client Macros:**
  Pre-configured HTTP clients for communicating with your API Gateway or other services. They automatically forward the current correlation ID header and propagate gateway errors to the caller.

- **Ready-to-publish Configuration:**  
  All settings are customizable via a single config file, making it easy to adapt the package to your environment.

This toolkit is designed to be flexible, extensible, and easy to integrate into any Laravel microservice architecture.

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
    'jwt_cache_ttl' => env('JWT_CACHE_TTL', 3600),
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

### LoadAccess

**Alias**: the string you assign to `load_access` in `middleware_aliases`, e.g. `load.access`.  
**Class**: `Kroderdev\LaravelMicroserviceCore\Http\Middleware\LoadAccess`

#### Description

Loads the authenticated user's roles and permissions, typically from a centralized permission service (such as an API Gateway or dedicated permissions microservice).  
By default, the `ValidateJwt` middleware will automatically load `roles` and `permissions` from the JWT payload if they are present.  
However, if you have a centralized permission service, you can use `LoadAccess` to fetch and hydrate the latest roles and permissions for the user, ensuring up-to-date authorization data.

#### Configuration (`config/microservice.php`)

```php
'permissions_cache_ttl' => env('PERMISSIONS_CACHE_TTL', 60),
```

#### Usage

Apply after JWT authentication, or use the `microservice.auth` group for both:

```php
// In routes/api.php
Route::middleware(['jwt.auth', 'load.access'])->group(function () {
    // protected routes with up-to-date permissions…
});

// Or simply:
Route::middleware('microservice.auth')->group(function () {
    // protected routes…
});
```

---

### Permission Middleware

**Alias**: the string you assign to `permission` in `middleware_aliases`, e.g. `permission`.  
**Class**: `Kroderdev\LaravelMicroserviceCore\Http\Middleware\PermissionMiddleware`

#### Description

Restricts access to routes based on user permissions.  
Checks if the authenticated user has the required permission(s) before allowing access.  
Returns a 403 Forbidden response if the user lacks the necessary permission.

#### Usage

```php
// In routes/api.php
Route::middleware(['permission:orders.view'])->get('/orders', [OrderController::class, 'index']);
Route::middleware(['permission:orders.create'])->post('/orders', [OrderController::class, 'store']);
```

---

### Role Middleware

**Alias**: the string you assign to `role` in `middleware_aliases`, e.g. `role`.  
**Class**: `Kroderdev\LaravelMicroserviceCore\Http\Middleware\RoleMiddleware`

#### Description

Restricts access to routes based on user roles.  
Checks if the authenticated user has the required role(s) before allowing access.  
Returns a 403 Forbidden response if the user does not have the required role.

#### Usage

```php
// In routes/api.php
Route::middleware(['role:admin'])->get('/admin', [AdminController::class, 'dashboard']);
Route::middleware(['role:manager'])->post('/reports', [ReportController::class, 'generate']);
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

### Auth Middleware Group

For convenience, you can use the built-in `microservice.auth` group, which runs:

1. **ValidateJwt** – decode JWT, hydrate `ExternalUser`, set `Auth::user()`  
2. **LoadAccess** – fetch roles & permissions via ApiGateway

#### Usage

```php
// routes/api.php

Route::middleware('microservice.auth')->group(function () {
    // Here you already have a valid ExternalUser with roles & permissions loaded:
    Route::get('/orders',   [OrderController::class, 'index']);
    Route::post('/orders',  [OrderController::class, 'store']);
});
```

You no longer need to stack `jwt.auth` + `load.access` manually—just use `microservice.auth` wherever you need full auth + authorization.

---

### Authorization

This package hooks into Laravel's Gate so Blade directives work with your roles and permissions. Any ability is treated as a permission by default; prefix an ability with `role:` or `permission:` to be explicit.

```blade
@can('posts.create')
    <!-- user can create posts -->
@endcan

@cannot('permission:posts.delete')
    <!-- no delete rights -->
@endcannot

@canany(['role:admin', 'permission:posts.update'])
    <!-- admin or user with update permission -->
@endcanany
```

---

## Endpoints

### Health Check Endpoint

This package exposes a JSON endpoint at `/api/health` providing basic service details.

#### Configuration (`config/microservice.php`)

```php
'health' => [
    'enabled' => env('HEALTH_ENDPOINT_ENABLED', true),
    'path'    => '/api/health',
],
```

When enabled (default), visiting `/api/health` returns:

```json
{
  "status": "ok",
  "app": "your-app-name",
  "environment": "testing",
  "laravel": "12.x-dev",
  "timestamp": "2025-01-01T12:00:00Z"
}
```

### HTTP Client Macros

Use `Http::apiGateway()` and its related macros for service-to-service calls. Each macro forwards the current correlation ID header when available.

If the gateway responds with an error (for example, a `503`), an `ApiGatewayException` is thrown and automatically returned to the client with the same status code.

---

## Public Release and Roadmap

This toolkit is still maturing. Upcoming plans include:

- Strengthening middleware modules
- Increasing test coverage
- Publishing more integration examples

A stable **v1.0** is on the horizon. Please share feedback or ideas in the issue tracker!

---

## Extending the Core

This package keeps its dependencies minimal so you can tailor each microservice as needed. Consider these optional packages when expanding your stack:

- **RabbitMQ Messaging** – Integrate [iamfarhad/LaravelRabbitMQ](https://github.com/iamfarhad/LaravelRabbitMQ) if you rely on RabbitMQ for queues. Install the package via Composer and update your queue connection to `rabbitmq`.
- **Prometheus Metrics** – Expose service metrics with [spatie/laravel-prometheus](https://github.com/spatie/laravel-prometheus). Publish the configuration and register the `/metrics` route in your service.
- **Additional Tools** – Depending on your requirements, you might add packages for service discovery, distributed tracing, or improved concurrency (e.g., Laravel Octane or Horizon).

These extensions remain completely optional, allowing you to keep the core lightweight while adding features specific to your environment.

---

## Documentation

Additional guides and examples are available in the [wiki](https://github.com/KroderDev/laravel-microservice-core/wiki).

---

## Contributing

Community contributions are encouraged! To get started:

1. Fork the repository and create a feature branch.
2. Commit your changes with tests when possible.
3. Open a pull request describing your work.

Feel free to start a discussion or issue if you have questions about the roadmap or want to propose a feature.
