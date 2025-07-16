<?php

namespace Kroderdev\LaravelMicroserviceCore\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Auth\GatewayGuard;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Exceptions\ApiGatewayException;
use Kroderdev\LaravelMicroserviceCore\Http\HealthCheckController;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\CorrelationId;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\LoadAccess;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\PermissionMiddleware;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\RoleMiddleware;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\ValidateJwt;
use Kroderdev\LaravelMicroserviceCore\Interfaces\ApiModelContract;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClient;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClientFactory;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;

class MicroserviceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Config
        $this->mergeConfigFrom(
            __DIR__.'/../config/microservice.php',
            'microservice'
        );

        $this->app->singleton(ApiGatewayClientFactory::class, fn () => new ApiGatewayClientFactory());
        $this->app->scoped(ApiGatewayClient::class, fn () => ApiGatewayClient::make());
        $this->app->bind(ApiGatewayClientInterface::class, fn ($app) => $app->make(ApiGatewayClientFactory::class)->default());
        $this->app->scoped(PermissionsClient::class, fn ($app) => new PermissionsClient($app->make(ApiGatewayClientInterface::class)));
        $this->app->singleton(AuthServiceClient::class, fn () => new AuthServiceClient());

        $this->app->singleton(\Illuminate\Foundation\Console\ModelMakeCommand::class, function ($app) {
            return new \Kroderdev\LaravelMicroserviceCore\Console\ModelMakeCommand($app['files']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        // Commands
        if ($this->app->runningInConsole()) {
            \Illuminate\Console\Application::starting(function ($artisan) {
                $artisan->add(new \Kroderdev\LaravelMicroserviceCore\Console\ModelMakeCommand($this->app['files']));
            });
        }

        // Publish config
        $this->publishes([
            __DIR__.'/../config/microservice.php' => config_path('microservice.php'),
        ], 'config');

        // Gateway Guard
        Auth::extend('gateway', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            $guard = new GatewayGuard(
                $name,
                $provider,
                $app['session.store'],
                $app->make('request'),
                $app->make(AuthServiceClient::class)
            );

            $guard->setCookieJar($app['cookie']);

            return $guard;
        });

        // Authorization gates
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

        // JWT Middleware alias
        if (! empty($aliases['jwt_auth'])) {
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
            $router->get('/'.$path, HealthCheckController::class);
        }

        // HTTP
        Http::macro('apiGateway', function () {
            // Correlation ID
            $header = config('microservice.correlation.header');
            $correlation = app()->bound('request') ? request()->header($header) : null;

            return Http::acceptJson()
                ->withHeaders($correlation ? [$header => $correlation] : [])
                ->baseUrl(config('microservice.api_gateway.url'))
                ->timeout(5)
                ->retry(2, 100, throw: false);
        });

        Http::macro('apiGatewayDirect', function () {
            // Correlation ID
            $header = config('microservice.correlation.header');
            $correlation = app()->bound('request') ? request()->header($header) : null;

            return Http::acceptJson()
                ->withHeaders($correlation ? [$header => $correlation] : [])
                ->baseUrl(config('microservice.api_gateway.url'))
                ->timeout(5);
        });

        Http::macro('apiGatewayWithToken', function (string $token) {
            // Correlation ID
            $header = config('microservice.correlation.header');
            $correlation = app()->bound('request') ? request()->header($header) : null;

            return Http::acceptJson()
                ->withToken($token)
                ->withHeaders($correlation ? [$header => $correlation] : [])
                ->baseUrl(config('microservice.api_gateway.url'))
                ->timeout(5)
                ->retry(2, 100, throw: false);
        });

        Http::macro('apiGatewayDirectWithToken', function (string $token) {
            // Correlation ID
            $header = config('microservice.correlation.header');
            $correlation = app()->bound('request') ? request()->header($header) : null;

            return Http::acceptJson()
                ->withToken($token)
                ->withHeaders($correlation ? [$header => $correlation] : [])
                ->baseUrl(config('microservice.api_gateway.url'))
                ->timeout(5);
        });

        // Validation
        Validator::extend('exists_remote', function ($attribute, $value, $parameters) {
            $model = $parameters[0] ?? null;
            $column = $parameters[1] ?? 'id';
            if (! $model || ! is_subclass_of($model, ApiModelContract::class)) {
                return false;
            }

            $values = is_array($value) ? $value : [$value];
            foreach ($values as $v) {
                if (! $model::find($v)) {
                    return false;
                }
            }

            return true;
        });

        // Exceptions
        $handler = $this->app->make(ExceptionHandler::class);

        if (method_exists($handler, 'renderable')) {
            $handler->renderable(function (ApiGatewayException $e, $request) {
                $status = $e->getStatusCode();
                $message = $e->getMessage();

                if (! $request->expectsJson()) {
                    abort($status, $message);
                }

                return response()->json(['error' => $message], $status);
            });
        }
    }
}
