# laravel-microservice-core

[![Packagist Version](https://img.shields.io/packagist/v/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core)
[![Downloads](https://img.shields.io/packagist/dt/kroderdev/laravel-microservice-core.svg)](https://packagist.org/packages/kroderdev/laravel-microservice-core)
[![License](https://img.shields.io/packagist/l/kroderdev/laravel-microservice-core.svg)](LICENSE)

A toolkit that turns **Laravel 12** into a lightweight base for distributed microservices. 

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

---

## Publish Configuration

To publish the configuration file, run:

```bash
php artisan vendor:publish --provider="Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider"
```

---

## Documentation

See the [wiki](https://github.com/KroderDev/laravel-microservice-core/wiki) for full documentation.