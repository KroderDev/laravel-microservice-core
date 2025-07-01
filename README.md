# laravel-microservice-core

[![Packagist Version](https://img.shields.io/packagist/v/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core)
[![Downloads](https://img.shields.io/packagist/dt/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core)
[![License](https://img.shields.io/packagist/l/kroderdev/laravel-microservice-core.svg)](LICENSE)

A toolkit that turns **Laravel 12** into a lightweight base for distributed microservices. 

## Features

- Middleware for JWT validation, correlation IDs and permission checks
- Authorization helpers using roles and permissions
- HTTP client macros and an API Gateway client
- Session guard and controllers for frontend authentication
- Health check endpoint at `/api/health`
- Base model for API gateway resources

---

## Documentation

See the [wiki](https://github.com/KroderDev/laravel-microservice-core/wiki) for full documentation.

---

## Quick start

Install via Composer:

```bash
composer require kroderdev/laravel-microservice-core
```

### Publish Configuration

After installation, publish the configuration file to your Laravel project:

```bash
php artisan vendor:publish --provider="Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider"
```

You can now customize the settings to match your microservice environment.