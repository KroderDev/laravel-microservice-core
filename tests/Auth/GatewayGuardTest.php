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
-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCLU1enq5mXQfzAEM5KwPtHO2TwYW+I9/Y1Ulm2daUk3mR0Ug++
G1nIGiM2OHMYwWG0O3k6i6dcQ7nZFreq7Dn4TqXbbeU22MTaRZi277RoR/Vv2a5/
cQGoOdKBIgs8N1UQsJw5XVg47iU4glYnzYLIiGvWLB+5uf8kQwMQ2YpXpwIDAQAB
AoGAdLBbxMFzBP0uXAp3TKKuke1L0Aw7JwNOgUA0hR2pL+TXS5kDOFyd6HsDrMDA
nSYx14rMMN2QUTUj7Y8aSxxIO85jzqinuuqdUB5h8bZZHeCDTBox8yUUEEAzPFLh
I5Aksmj/WWOAAZjTxge8GTfL8fhC2XoRwBWs/zOYce1OAhECQQDj8i7Gu2pson8N
iRxnFxEYgsRvJLpJcMkzTnHw8V/U1EDEmVCpOJtIL11Ydd+Vvl5M6iT6G+6wow36
rECXF9FfAkEAnHkGYXaY5eZWS5ax21N3ktc58JSAFMmvnXZslRW1OF9XTwhxfSb5
n1AAcxXtWuedbYFNNuf/90D8QBEgexY2uQJBAIHeqs3pW7I3RsIUe0009DWN05Mr
TsOm8cs8h2hqbVoZ8CjS3QT8zmPrMHjE97UeOCYERTsGjRCwZbeLSmWLWWsCQBmd
FhZOO6kmk2m8OVEV0LUQ1kMzi+PbQAwenpeo/glEUh51214JS0Nw7SHprPj8gSCz
0dfzEkt/L8utAgwkDsECQQCmkR0Ak3KNOmZrkECuRmrQ6yJ0VK/Pxl8R6oz1Wohu
0vZG84wSA1KxbRDEsAt84FlocT3SS74HjBetys0fyOW9
-----END RSA PRIVATE KEY-----
EOD;

        $this->publicKey = <<<'EOD'
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCLU1enq5mXQfzAEM5KwPtHO2Tw
YW+I9/Y1Ulm2daUk3mR0Ug++G1nIGiM2OHMYwWG0O3k6i6dcQ7nZFreq7Dn4TqXb
beU22MTaRZi277RoR/Vv2a5/cQGoOdKBIgs8N1UQsJw5XVg47iU4glYnzYLIiGvW
LB+5uf8kQwMQ2YpXpwIDAQAB
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
                $app->make(AuthServiceClient::class)
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
