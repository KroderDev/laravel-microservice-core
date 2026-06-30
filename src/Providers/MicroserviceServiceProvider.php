<?php

namespace Kroderdev\LaravelMicroserviceCore\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;
use Kroderdev\LaravelMicroserviceCore\Contracts\ServiceClientInterface;
use Kroderdev\LaravelMicroserviceCore\Exceptions\ServiceClientException;
use Kroderdev\LaravelMicroserviceCore\Http\HealthCheckController;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\CorrelationId;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\LoadAccess;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\PermissionMiddleware;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\RoleMiddleware;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\ValidateJwt;
use Kroderdev\LaravelMicroserviceCore\Services\JwtValidator;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;
use Kroderdev\LaravelMicroserviceCore\Services\ServiceClient;
use Kroderdev\LaravelMicroserviceCore\Services\ServiceClientFactory;

class MicroserviceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/microservice.php',
            'microservice'
        );

        $this->app->singleton(ServiceClientFactory::class, fn () => new ServiceClientFactory());
        $this->app->scoped(ServiceClient::class, fn () => ServiceClient::toGateway());
        $this->app->bind(ServiceClientInterface::class, fn ($app) => $app->make(ServiceClientFactory::class)->default());
        $this->app->scoped(PermissionsClient::class, fn ($app) => new PermissionsClient($app->make(ServiceClientInterface::class)));
        $this->app->singleton(JwtValidator::class, fn () => new JwtValidator());
    }

    public function boot(Router $router): void
    {
        $this->publishes([
            __DIR__.'/../config/microservice.php' => config_path('microservice.php'),
        ], 'config');

        Gate::before(function ($user, string $ability) {
            if (! $user instanceof AccessUserInterface) {
                return null;
            }

            if (str_starts_with($ability, 'role:')) {
                $role = substr($ability, 5);

                return $user->hasRole($role);
            }

            if (str_starts_with($ability, 'permission:')) {
                $ability = substr($ability, 11);
            }

            return $user->hasPermissionTo($ability);
        });

        $aliases = config('microservice.middleware_aliases', []);

        if (! empty($aliases['jwt_auth'])) {
            $router->aliasMiddleware($aliases['jwt_auth'], ValidateJwt::class);
        }

        if (! empty($aliases['correlation_id'])) {
            $router->aliasMiddleware($aliases['correlation_id'], CorrelationId::class);
            $router->prependMiddlewareToGroup('api', CorrelationId::class);
        }

        if (! empty($aliases['role'] ?? '')) {
            $router->aliasMiddleware($aliases['role'], RoleMiddleware::class);
        }

        if (! empty($aliases['permission'] ?? '')) {
            $router->aliasMiddleware($aliases['permission'], PermissionMiddleware::class);
        }

        if (! empty($aliases['load_access'] ?? '')) {
            $router->aliasMiddleware($aliases['load_access'], LoadAccess::class);
        }

        $router->middlewareGroup('microservice.auth', [
            ValidateJwt::class,
            LoadAccess::class,
        ]);

        if (config('microservice.health.enabled', true)) {
            $path = ltrim(config('microservice.health.path', '/health'), '/');
            $router->get('/'.$path, HealthCheckController::class);
        }

        $this->registerHttpMacros();

        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'renderable')) {
            $handler->renderable(function (ServiceClientException $e, $request) {
                $status = $e->getStatusCode();
                $message = $e->getMessage();

                if (! $request->expectsJson()) {
                    abort($status, $message);
                }

                return response()->json(['error' => $message], $status);
            });
        }
    }

    protected function registerHttpMacros(): void
    {
        Http::macro('service', function (string $name = 'gateway') {
            $config = config("microservice.services.registry.{$name}", []);
            $url = $config['url'] ?? config('microservice.services.default', 'http://gateway.local');
            $timeout = $config['timeout'] ?? 5;
            $retries = $config['retries'] ?? 2;
            $correlationHeader = config('microservice.tracing.correlation.header', 'X-Correlation-ID');
            $correlation = app()->bound('request') ? request()->header($correlationHeader) : null;

            return Http::acceptJson()
                ->withHeaders($correlation ? [$correlationHeader => $correlation] : [])
                ->baseUrl($url)
                ->timeout($timeout)
                ->retry($retries, 100, throw: false);
        });

        Http::macro('serviceDirect', function (string $name = 'gateway') {
            $config = config("microservice.services.registry.{$name}", []);
            $url = $config['url'] ?? config('microservice.services.default', 'http://gateway.local');
            $timeout = $config['timeout'] ?? 5;
            $correlationHeader = config('microservice.tracing.correlation.header', 'X-Correlation-ID');
            $correlation = app()->bound('request') ? request()->header($correlationHeader) : null;

            return Http::acceptJson()
                ->withHeaders($correlation ? [$correlationHeader => $correlation] : [])
                ->baseUrl($url)
                ->timeout($timeout);
        });

        Http::macro('serviceWithToken', function (string $name, string $token) {
            $config = config("microservice.services.registry.{$name}", []);
            $url = $config['url'] ?? config('microservice.services.default', 'http://gateway.local');
            $timeout = $config['timeout'] ?? 5;
            $retries = $config['retries'] ?? 2;
            $correlationHeader = config('microservice.tracing.correlation.header', 'X-Correlation-ID');
            $correlation = app()->bound('request') ? request()->header($correlationHeader) : null;

            return Http::acceptJson()
                ->withToken($token)
                ->withHeaders($correlation ? [$correlationHeader => $correlation] : [])
                ->baseUrl($url)
                ->timeout($timeout)
                ->retry($retries, 100, throw: false);
        });

        Http::macro('serviceDirectWithToken', function (string $name, string $token) {
            $config = config("microservice.services.registry.{$name}", []);
            $url = $config['url'] ?? config('microservice.services.default', 'http://gateway.local');
            $timeout = $config['timeout'] ?? 5;
            $correlationHeader = config('microservice.tracing.correlation.header', 'X-Correlation-ID');
            $correlation = app()->bound('request') ? request()->header($correlationHeader) : null;

            return Http::acceptJson()
                ->withToken($token)
                ->withHeaders($correlation ? [$correlationHeader => $correlation] : [])
                ->baseUrl($url)
                ->timeout($timeout);
        });
    }
}
