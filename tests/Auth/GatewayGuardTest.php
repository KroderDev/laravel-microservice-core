<?php

namespace Tests\Auth;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Auth\GatewayGuard;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;
use Kroderdev\LaravelMicroserviceCore\Services\JwtValidator;
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
    protected string $privateKey;

    protected string $publicKey;

    protected string $tmpKeyPath;

    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(AuthServiceClient::class, fn () => new FakeAuthServiceClient());

        $this->privateKey = <<<'EOD'
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDJYVxEtXcAuoTA
SAPJBwIhrDWiwCpJFl0qi8A8BIVecuYLxrOJ6KkI1iDxI6z2pDFsu23SeAR8Dma8
oYBdthIhwHwsx6X7RfFvW9mcrREALfK1Stm8lPvWhqsMRW7c68cRyBx1SXeTpOq+
qtBtiN76w3vU1SVrQKHX5LzenoLmaO0gB4VcK8VZaWAHzvq9RBr2r0YvNdkTTRat
Cgu+Cb6nRYHuq7qsWxuAFNHjA12alHCgFRAFLQ8/478YvVsi8oy3oM/0ERouvzDW
7g/V1aCd56WkqI1bg/jIvg8d3gwwsFn9HeM8HYwFGtZk9gU2yY6u4EItyMaadzks
UtVqDJALAgMBAAECggEAT2h7XEKcHubtou6iw3nmfsWrzrXs1q4hZb3+uwvjVVeE
W/9p24cpZGkfS0cQlJ8xOBl+WEBoMEzzeQ1ME2fQpYuDy5qcWkV9yzYSTMQ30HTN
4GwYCNNMrQ6kUy3r5eR5NotMoXkrVEZzJGUx1AdlOcesxEOaQj0VtI6nZTdnEZh6
Rw7ySy8/lfK33yjXgukVj4vHDu9gVBXKPxXISTfsLAYB7jm0Dl8HzitET59s9zHX
mzoMLC0EQd1sm/+X38g332g59lTVBLryxqkItHQ+OmT3lgXMPcRGRJZ17LlevVrG
2mcDUG+SwF/g07h6/dTtbp9dh2T3fYV9+PhdQxRXCQKBgQDp+zaRMw62/YX4iOUU
EqLq61oU3Zz46tIyymcINFrCa4nt5YYy/MVBKWZDmSqota1ZeR5C7OKiKtcVNnKo
0AgkCLplCOxVoD4loe4DZUd+1P3Lpt7WOzo9LQ5S/43vmcdqLbIMOatyXSBrXQ/A
yxdJ6lYQuQP8nNyFVz+VPN3CEwKBgQDcVMPI9B14Lzz7h5quBUUj0FaWoIAaRyiD
H4ZTrHjPP/7cgwkgUwGnqqaWJSgdEO//gCSMfp1TlHnAIksiOub3Cdj57aMWdIpU
q4jFE2cy7leMMD8C95GOoClqxGWQVzzmOq01YEw8rMqTBB9Hs5TLkLpGtQzCKPGm
H2DDnKz5KQKBgHBgSxDpqMOSd+mqNfuyB7U0XAtxdJbIkTP7qghyvLRr2c78Ubvj
Uwm8zHTi924X863pUfNqul6QnMR/ZgpV/9LurjcgVgG5+J8yapIO7oun2E0bVCMo
Rwxiu2J5gr306aXBVKYyfHls6Mkn5Qz/favudG+LrLKC1BbmlI8ksI0pAoGAapw9
S9wK8l9xE4gu6Ss1pDmn3CR3N7/cs22qkowuvKGLkWl6HgDsGRPynU4HVeEfL7Ly
fGS7fXinXLd7QWnc3gYOzggVVMU5NfT2Ld3Qno1DIVsq8iWWcbu/rqCvQCNmHUSh
EOWynd1B+9cPx6L2SXWTHKl4Le6f1rDyIlSKQdkCgYB/fll5UaqQh4cT8GvGQU9d
1XjO76gj+sLv5kv6LrABqM3/JOQni68dkmgjV6XKbuMmHqb18px73IZnP0xs6JEN
FlfI072bc2bu2QAo82BvxOP+UtrzcYd96Hlrd4+u622nMFkXh2dV1vms1WN4VT++
MlHx0kBm1t9dJCcRs0ZWrQ==
-----END PRIVATE KEY-----
EOD;

        $this->publicKey = <<<'EOD'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyWFcRLV3ALqEwEgDyQcC
Iaw1osAqSRZdKovAPASFXnLmC8azieipCNYg8SOs9qQxbLtt0ngEfA5mvKGAXbYS
IcB8LMel+0Xxb1vZnK0RAC3ytUrZvJT71oarDEVu3OvHEcgcdUl3k6TqvqrQbYje
+sN71NUla0Ch1+S83p6C5mjtIAeFXCvFWWlgB876vUQa9q9GLzXZE00WrQoLvgm+
p0WB7qu6rFsbgBTR4wNdmpRwoBUQBS0PP+O/GL1bIvKMt6DP9BEaLr8w1u4P1dWg
neelpKiNW4P4yL4PHd4MMLBZ/R3jPB2MBRrWZPYFNsmOruBCLcjGmnc5LFLVagyQ
CwIDAQAB
-----END PUBLIC KEY-----
EOD;

        $this->tmpKeyPath = sys_get_temp_dir().'/gateway_guard_public.key';
        file_put_contents($this->tmpKeyPath, $this->publicKey);
        Config::set('microservice.auth.jwt_public_key', $this->tmpKeyPath);
        Config::set('microservice.auth.jwt_algorithm', 'RS256');

        Auth::extend('gateway', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            return new GatewayGuard(
                $name,
                $provider,
                $app['session.store'],
                $app->make('request'),
                $app->make(AuthServiceClient::class),
                $app->make(JwtValidator::class)
            );
        });

        Config::set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => ExternalUser::class,
        ]);
        Config::set('auth.guards.gateway', ['driver' => 'gateway', 'provider' => 'users']);
    }

    protected function tearDown(): void
    {
        if (isset($this->tmpKeyPath) && file_exists($this->tmpKeyPath)) {
            unlink($this->tmpKeyPath);
        }

        parent::tearDown();
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
    public function validates_token_without_logging_key_contents()
    {
        $guard = Auth::guard('gateway');

        $payload = [
            'sub' => 'user-123',
            'iss' => 'auth-service',
            'exp' => time() + 60,
        ];

        $jwt = JWT::encode($payload, $this->privateKey, 'RS256');
        Session::put($guard->getName(), $jwt);

        Log::spy();

        $user = $guard->user();

        $this->assertInstanceOf(ExternalUser::class, $user);
        $this->assertSame($jwt, Session::get($guard->getName()));

        Log::shouldHaveReceived('info')
            ->with('Loaded JWT public key for token validation', \Mockery::on(function ($context) {
                return array_key_exists('path', $context) && ! array_key_exists('publicKey', $context);
            }))
            ->once();
    }

    /** @test */
    public function token_method_returns_current_token()
    {
        $guard = Auth::guard('gateway');
        $guard->attempt(['email' => 'foo']);

        $this->assertSame('token123', $guard->token());
    }
}
