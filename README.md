# Laravel Microservice Core

[![Packagist Version](https://img.shields.io/packagist/v/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core)
[![Downloads](https://img.shields.io/packagist/dt/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core)
[![License](https://img.shields.io/packagist/l/kroderdev/laravel-microservice-core.svg)](LICENSE)

A **Laravel package** that simplifies building microservices in **distributed architectures**. It provides authentication, request handling, and service-to-service communication tools to make Laravel scalable and production-ready in microservice environments.

## Key Features

This package packages common microservice concerns so you can focus on your service logic:

- JWT authentication middleware and a session guard for frontend interactions
- Correlation ID propagation for tracing requests across services
- Role and permission gates with convenient helpers
- HTTP client macros and a configurable API Gateway client
- Base model and query builder class for working with remote resources through the gateway
- Optional health check endpoint out of the box

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

Configure your API gateway URL and JWT settings in `config/microservice.php`.
Then extend the base model to interact with remote resources:

Scaffold your remote model via the new Artisan command:

```bash
php artisan make:model RemoteUser --remote
```

This will generate a RemoteUser model that extends the core Model class with remote-resource support.

```php
use Kroderdev\LaravelMicroserviceCore\Models\Model;

class RemoteUser extends Model
{
    protected static string $endpoint = '/users';
    protected $fillable = ['id', 'name'];
}

$users = RemoteUser::all();
```

Add the provided middleware to your routes to validate JWTs and propagate correlation IDs:

```php
Route::middleware(['jwt.auth'])->group(function () {
    Route::get('/profile', fn () => 'ok');
});
```

## Documentation

Full documentation lives in the [project wiki](https://github.com/KroderDev/laravel-microservice-core/wiki).

## Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).
