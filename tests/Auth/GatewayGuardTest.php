<?php

namespace Tests\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Auth\GatewayGuard;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;
use Orchestra\Testbench\TestCase;

class FakeAuthServiceClient extends AuthServiceClient
{
    public string $token = 'token123';

    public string $refreshed = 'token456';

    public array $user = [
        'id' => '1',
        'roles' => ['admin'],
        'permissions' => ['edit'],
    ];

    public function login(array $credentials): array
    {
        return ['access_token' => $this->token, 'user' => $this->user];
    }

    public function me(string $token): array
    {
        return $this->user;
    }

    public function refresh(string $token): array
    {
        return ['access_token' => $this->refreshed];
    }
}

class GatewayGuardTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(AuthServiceClient::class, fn () => new FakeAuthServiceClient());

        Auth::extend('gateway', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            return new GatewayGuard(
                $name,
                $provider,
                $app['session.store'],
                $app->make('request'),
                $app->make(AuthServiceClient::class)
            );
        });

        Config::set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => ExternalUser::class,
        ]);
        Config::set('auth.guards.gateway', ['driver' => 'gateway', 'provider' => 'users']);
    }

    /** @test */
    public function attempt_sets_user_and_token()
    {
        $guard = Auth::guard('gateway');
        $this->assertTrue($guard->attempt(['email' => 'foo']));
        $this->assertInstanceOf(ExternalUser::class, $guard->user());
        $this->assertEquals('token123', Session::get($guard->getName()));
    }

    /** @test */
    public function refreshes_token_when_invalid()
    {
        $guard = Auth::guard('gateway');
        Session::put($guard->getName(), 'expired');
        $user = $guard->user();
        $this->assertInstanceOf(ExternalUser::class, $user);
        $this->assertEquals('token456', Session::get($guard->getName()));
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasPermissionTo('edit'));
    }

    /** @test */
    public function can_disable_access_loading()
    {
        Config::set('microservice.gateway_guard.load_access', false);
        $guard = Auth::guard('gateway');
        Session::put($guard->getName(), 'expired');
        $user = $guard->user();
        $this->assertEmpty($user->getRoleNames());
    }

    /** @test */
    public function token_method_returns_current_token()
    {
        $guard = Auth::guard('gateway');
        $guard->attempt(['email' => 'foo']);

        $this->assertSame('token123', $guard->token());
    }
}
