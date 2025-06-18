<?php

namespace Kroderdev\LaravelMicroserviceCore\Providers;

use Illuminate\Foundation\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Http\HealthCheckController;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\LoadAccess;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\PermissionMiddleware;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\RoleMiddleware;
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
        if (!empty($aliases['jwt_auth'])) {
            $router->aliasMiddleware($aliases['jwt_auth'], ValidateJwt::class);
        }

        // Correlation ID middleware alias
        if (! empty($aliases['correlation_id'])) {
            $router->aliasMiddleware($aliases['correlation_id'], CorrelationId::class);
            $router->prependMiddlewareToGroup('api', CorrelationId::class);
        }

        // Role middleware alias
        if (! empty($aliases['role'] ?? '')) {
            $router->aliasMiddleware($aliases['role'], RoleMiddleware::class);
        }

        // Permission middleware alias
        if (! empty($aliases['permission'] ?? '')) {
            $router->aliasMiddleware($aliases['permission'], PermissionMiddleware::class);
        }

        // LoadAccess middleware alias
        if (! empty($aliases['load_access'] ?? '')) {
            $router->aliasMiddleware($aliases['load_access'], LoadAccess::class);
        }

        // Auth middleware group
        $router->middlewareGroup('microservice.auth', [
            ValidateJwt::class,
            LoadAccess::class,
        ]);

        // Health check route
        if (config('microservice.health.enabled', true)) {
            $path = ltrim(config('microservice.health.path', '/api/health'), '/');
            $router->get('/' . $path, HealthCheckController::class);
        }

        // HTTP
        Http::macro('apiGateway', function () {
            // Correlation ID
            $header = config('microservice.correlation.header');
            $correlation = app()->bound('request') ? request()->header($header) : null;

            return Http::acceptJson()
                ->withHeaders($correlation ? [$header => $correlation] : [])
                ->baseUrl(config('services.api_gateway.url'))
                ->timeout(5)
                ->retry(2, 100);
        });

        Http::macro('apiGatewayDirect', function () {
            // Correlation ID
            $header = config('microservice.correlation.header');
            $correlation = app()->bound('request') ? request()->header($header) : null;

            return Http::acceptJson()
                ->withHeaders($correlation ? [$header => $correlation] : [])
                ->baseUrl(config('services.api_gateway.url'))
                ->timeout(5);
        });

        Http::macro('apiGatewayWithToken', function (string $token) {
            // Correlation ID
            $header = config('microservice.correlation.header');
            $correlation = app()->bound('request') ? request()->header($header) : null;

            return Http::acceptJson()
                ->withToken($token)
                ->withHeaders($correlation ? [$header => $correlation] : [])
                ->baseUrl(config('services.api_gateway.url'))
                ->timeout(5)
                ->retry(2, 100);
        });

        Http::macro('apiGatewayDirectWithToken', function (string $token) {
            // Correlation ID
            $header = config('microservice.correlation.header');
            $correlation = app()->bound('request') ? request()->header($header) : null;

            return Http::acceptJson()
                ->withToken($token)
                ->withHeaders($correlation ? [$header => $correlation] : [])
                ->baseUrl(config('services.api_gateway.url'))
                ->timeout(5);
        });
    }
}