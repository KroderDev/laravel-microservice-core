<?php

namespace Tests\Middleware;

use Illuminate\Support\Facades\Route;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\ValidateJwt;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;
use Orchestra\Testbench\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
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
        
        $this->app->singleton(PermissionsClient::class, fn () => new class {
            public function getAccessFor($user) {
                return ['roles' => ['tester'], 'permissions' => ['view dashboard']];
            }
        });

        $this->privateKey = <<<EOD
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

        $this->publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCLU1enq5mXQfzAEM5KwPtHO2Tw
YW+I9/Y1Ulm2daUk3mR0Ug++G1nIGiM2OHMYwWG0O3k6i6dcQ7nZFreq7Dn4TqXb
beU22MTaRZi277RoR/Vv2a5/cQGoOdKBIgs8N1UQsJw5XVg47iU4glYnzYLIiGvW
LB+5uf8kQwMQ2YpXpwIDAQAB
-----END PUBLIC KEY-----
EOD;

    file_put_contents($this->tmpKeyPath, $this->publicKey);

        // Secured route
        Route::middleware(ValidateJwt::class)->get('/secured', fn () => response()->json(['ok' => true]));
        
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
        $this->tmpKeyPath = sys_get_temp_dir() . '/tmp_public.key';
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
            'Authorization' => 'Bearer invalid.token.here'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_accepts_valid_token()
    {
        // Crear token válido con clave privada
        $payload = [
            'sub' => 'user-123',
            'iss' => 'auth-service',
            'exp' => time() + 60,
        ];

        $jwt = JWT::encode($payload, $this->privateKey, 'RS256');

        // Guardar clave pública temporal
        file_put_contents(__DIR__ . '/tmp_public.pem', $this->publicKey);

        $response = $this->get('/secured', [
            'Authorization' => "Bearer $jwt"
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        unlink(__DIR__ . '/tmp_public.pem');
    }
}