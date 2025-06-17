<?php

namespace Kroderdev\LaravelMicroserviceCore\Providers;

use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Middleware\ValidateJwt;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClient;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClientFactory;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;

class MicroserviceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ApiGatewayClientFactory::class, fn () => new ApiGatewayClientFactory());
        $this->app->singleton(ApiGatewayClient::class, fn () => ApiGatewayClient::make());
        $this->app->bind(ApiGatewayClientInterface::class, fn($app) => $app->make(ApiGatewayClientFactory::class)->default()); 
        $this->app->singleton(PermissionsClient::class, fn($app) => new PermissionsClient($app->make(ApiGatewayClientInterface::class)));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // HTTP
        Http::macro('apiGateway', function () {
            return Http::acceptJson()
                ->baseUrl(config('services.api_gateway.url'))
                ->timeout(5)
                ->retry(2, 100);
        });

        Http::macro('apiGatewayDirect', function () {
            return Http::acceptJson()
                ->baseUrl(config('services.api_gateway.url'))
                ->timeout(5);
        });

        Http::macro('apiGatewayWithToken', function (string $token) {
            return Http::acceptJson()
                ->withToken($token)
                ->baseUrl(config('services.api_gateway.url'))
                ->timeout(5)
                ->retry(2, 100);
        });

        Http::macro('apiGatewayDirectWithToken', function (string $token) {
            return Http::acceptJson()
                ->withToken($token)
                ->baseUrl(config('services.api_gateway.url'))
                ->timeout(5);
        });

        // Middlewares
        $this->app->make(Kernel::class)->pushMiddleware(ValidateJwt::class);
    }
}