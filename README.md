# Laravel Microservice Core

[![Packagist Version](https://img.shields.io/packagist/v/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core)
[![Downloads](https://img.shields.io/packagist/dt/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core)
[![License](https://img.shields.io/packagist/l/kroderdev/laravel-microservice-core.svg)](LICENSE)

A **Laravel package** that provides the infrastructure to build and operate services in a **distributed architecture**. It delivers JWT authentication, role-based authorization, distributed request tracing, and a multi-service HTTP client — so you can focus on your service logic instead of boilerplate.

## Key Features

- **JWT authentication middleware** with RSA/ECDSA/EdDSA support via public key or JWKS (Keycloak-ready)
- **OpenID Connect integration** for role and permission extraction from token claims
- **Role and permission middleware** with Laravel Gate integration (`role:`, `permission:` prefixes)
- **Multi-service HTTP client** with configurable service registry, correlation ID propagation, timeout, and retry
- **Distributed request tracing** via correlation IDs (W3C Trace Context and OpenTelemetry on the roadmap)
- **Health check endpoint** out of the box

## Quick start

Install the package via Composer:

```bash
composer require kroderdev/laravel-microservice-core
```

Publish the configuration to customize defaults:

```bash
php artisan vendor:publish --provider="Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider"
```

## Basic usage

### Service communication

Configure your services in `config/microservice.php` under `services.registry`:

```php
'services' => [
    'registry' => [
        'gateway' => [
            'url' => env('API_GATEWAY_URL', 'http://gateway.local'),
            'timeout' => 5,
            'retries' => 2,
        ],
        'users' => [
            'url' => env('USERS_SERVICE_URL'),
            'timeout' => 10,
            'retries' => 3,
        ],
    ],
],
```

Then call any registered service using HTTP macros or the `ServiceClient`:

```php
use Kroderdev\LaravelMicroserviceCore\Services\ServiceClient;
use Illuminate\Support\Facades\Http;

// Via HTTP macro (auto-attaches correlation ID)
$response = Http::service('users')->get('/api/users');
$response = Http::service('gateway')->post('/auth/login', $credentials);

// With a bearer token
$response = Http::serviceWithToken('users', $token)->get('/api/profile');

// Via ServiceClient (instance methods)
$client = ServiceClient::to('users');
$client->withToken($token);
$users = $client->get('/api/users');
```

### JWT authentication

Add the provided middleware to your routes:

```php
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\ValidateJwt;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\LoadAccess;

// Individual middleware
Route::middleware(['jwt.auth'])->group(function () {
    Route::get('/profile', fn () => 'ok');
});

// Convenience group (JWT validation + permission loading)
Route::middleware(['microservice.auth'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'));
});
```

### Role and permission middleware

```php
Route::middleware(['jwt.auth', 'load.access', 'role:admin'])->group(function () {
    Route::get('/admin', fn () => 'admin area');
});

Route::middleware(['jwt.auth', 'load.access', 'permission:posts.create'])->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
});
```

### Gate authorization

```php
Gate::allows('role:admin');
Gate::allows('permission:posts.create');
```

### Health check

Enabled by default at `GET /health`. Configure or disable in `config/microservice.php`:

```php
'health' => [
    'enabled' => true,
    'path' => '/health',
],
```

## Documentation

Full documentation lives in the [project wiki](https://github.com/KroderDev/laravel-microservice-core/wiki).

## Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
