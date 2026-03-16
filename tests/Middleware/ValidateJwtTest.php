<?php

namespace Tests\Middleware;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Route;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\LoadAccess;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\PermissionMiddleware;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\ValidateJwt;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;
use Orchestra\Testbench\TestCase;
use Tests\Services\FakeGatewayClient;

class ValidateJwtTest extends TestCase
{
    protected string $privateKey;

    protected string $publicKey;

    protected string $tmpKeyPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ApiGatewayClientInterface::class, fn () => new FakeGatewayClient());

        $this->app['router']->aliasMiddleware('permission', PermissionMiddleware::class);

        $this->app->singleton(PermissionsClient::class, fn () => new class () {
            public function getAccessFor($user)
            {
                return ['roles' => ['tester'], 'permissions' => ['view.dashboard']];
            }
        });

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

        file_put_contents($this->tmpKeyPath, $this->publicKey);

        // Secured route
        Route::middleware(ValidateJwt::class)->get('/secured', fn () => response()->json(['ok' => true]));
        // Secured with permissions route
        Route::middleware([ValidateJwt::class, LoadAccess::class, 'permission:view.dashboard'])->get('/secured-with-permissions', fn () => response()->json(['ok' => true]));
        // Secured with permissions route
        Route::middleware([ValidateJwt::class, LoadAccess::class, 'permission:place.order'])->get('/secured-with-permissions-2', fn () => response()->json(['ok' => true]));
        // Route for inspecting claims extracted from the token
        Route::middleware(ValidateJwt::class)->get('/oidc-claims', function () {
            $user = auth()->user();

            return response()->json([
                'id' => $user?->getAuthIdentifier(),
                'roles' => $user?->getRoleNames(),
                'permissions' => $user?->getPermissions(),
            ]);
        });
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpKeyPath)) {
            unlink($this->tmpKeyPath);
        }

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $this->tmpKeyPath = sys_get_temp_dir().'/tmp_public.key';
        $app['config']->set('microservice.auth.jwt_public_key', $this->tmpKeyPath);
        $app['config']->set('microservice.auth.jwt_algorithm', 'RS256');
    }

    /** @test */
    public function test_rejects_request_without_token()
    {
        $response = $this->get('/secured');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_rejects_invalid_token()
    {
        $response = $this->get('/secured', [
            'Authorization' => 'Bearer invalid.token.here',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_accepts_valid_token()
    {
        $payload = [
            'sub' => 'user-123',
            'iss' => 'auth-service',
            'exp' => time() + 60,
        ];

        $jwt = JWT::encode($payload, $this->privateKey, 'RS256');

        file_put_contents(__DIR__.'/tmp_public.pem', $this->publicKey);

        $response = $this->get('/secured', [
            'Authorization' => "Bearer $jwt",
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        unlink(__DIR__.'/tmp_public.pem');
    }

    /** @test */
    public function test_rejects_valid_token_and_bad_permissions()
    {
        $payload = [
            'sub' => 'user-123',
            'iss' => 'auth-service',
            'exp' => time() + 60,
        ];

        $jwt = JWT::encode($payload, $this->privateKey, 'RS256');

        file_put_contents(__DIR__.'/tmp_public.pem', $this->publicKey);

        $response = $this->get('/secured-with-permissions-2', [
            'Authorization' => "Bearer $jwt",
        ]);

        $response->assertStatus(403);

        unlink(__DIR__.'/tmp_public.pem');
    }

    /** @test */
    public function test_accepts_valid_token_and_permissions()
    {
        $payload = [
            'sub' => 'user-123',
            'iss' => 'auth-service',
            'exp' => time() + 60,
        ];

        $jwt = JWT::encode($payload, $this->privateKey, 'RS256');

        file_put_contents(__DIR__.'/tmp_public.pem', $this->publicKey);

        $response = $this->get('/secured-with-permissions', [
            'Authorization' => "Bearer $jwt",
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        unlink(__DIR__.'/tmp_public.pem');
    }

    /** @test */
    public function test_extracts_oidc_roles_and_permissions()
    {
        config()->set('microservice.auth.user_identifier_claim', 'sub');
        config()->set('microservice.auth.oidc.enabled', true);
        config()->set('microservice.auth.oidc.client_id', 'bff');
        config()->set('microservice.auth.oidc.map_primary_roles_to_permissions', false);

        $payload = [
            'sub' => 'kc-user-123',
            'exp' => time() + 60,
            'realm_access' => ['roles' => ['realm-admin']],
            'resource_access' => [
                'bff' => ['roles' => ['view-dashboard', 'place-order']],
            ],
        ];

        $jwt = JWT::encode($payload, $this->privateKey, 'RS256');

        $response = $this->get('/oidc-claims', [
            'Authorization' => "Bearer $jwt",
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'id' => 'kc-user-123',
            'roles' => ['realm-admin'],
            'permissions' => ['view-dashboard', 'place-order'],
        ]);
    }
}
