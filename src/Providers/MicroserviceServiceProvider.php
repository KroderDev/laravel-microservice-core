<?php

namespace Kroderdev\LaravelMicroserviceCore\Providers;

use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\ValidateJwt;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClient;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClientFactory;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\CorrelationId;

class MicroserviceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Config
        $this->mergeConfigFrom(
            __DIR__.'/../config/microservice.php', 'microservice'
        );

        $this->app->singleton(ApiGatewayClientFactory::class, fn () => new ApiGatewayClientFactory());
        $this->app->singleton(ApiGatewayClient::class, fn () => ApiGatewayClient::make());
        $this->app->bind(ApiGatewayClientInterface::class, fn($app) => $app->make(ApiGatewayClientFactory::class)->default()); 
        $this->app->singleton(PermissionsClient::class, fn($app) => new PermissionsClient($app->make(ApiGatewayClientInterface::class)));
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/microservice.php' => config_path('microservice.php'),
        ], 'config');

        $aliases = config('microservice.middleware_aliases', []);

        // JWT Middleware alias
        $jwtAlias = $aliases['jwt_auth'] ?? null;
        if (!empty($jwtAlias)) {
            $router->aliasMiddleware($jwtAlias, ValidateJwt::class);
        }

        // Correlation ID middleware alias
        $corrAlias = $aliases['correlation_id'] ?? null;
        if (! empty($corrAlias)) {
            $router->aliasMiddleware($corrAlias, CorrelationId::class);
            $router->prependMiddlewareToGroup('api', CorrelationId::class);
        }

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
    }
}