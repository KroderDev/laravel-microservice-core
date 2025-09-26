<?php

namespace Tests\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Services\JwtValidator;
use Orchestra\Testbench\TestCase;

class JwtValidatorTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    /** @test */
    public function it_decodes_tokens_using_jwks()
    {
        $privateKey = <<<'EOD'
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

        $keyResource = openssl_pkey_get_private($privateKey);
        $this->assertNotFalse($keyResource, 'Unable to load private key for test');
        $publicDetails = openssl_pkey_get_details($keyResource);
        $jwksUrl = 'https://keycloak.test/realms/demo/protocol/openid-connect/certs';

        $n = rtrim(strtr(base64_encode($publicDetails['rsa']['n']), '+/', '-_'), '=');
        $e = rtrim(strtr(base64_encode($publicDetails['rsa']['e']), '+/', '-_'), '=');

        Http::fake([
            $jwksUrl => Http::response([
                'keys' => [[
                    'kty' => 'RSA',
                    'kid' => 'test-key',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'n' => $n,
                    'e' => $e,
                ]],
            ], 200),
        ]);

        config()->set('microservice.auth.jwt_public_key', null);
        config()->set('microservice.auth.jwt_algorithm', 'RS256');
        config()->set('microservice.auth.jwt_cache_ttl', 60);
        config()->set('microservice.auth.oidc.jwks_url', $jwksUrl);

        $validator = new JwtValidator();

        $payload = [
            'sub' => 'jwks-user',
            'exp' => time() + 60,
        ];

        $token = JWT::encode($payload, $privateKey, 'RS256', 'test-key');

        $decoded = $validator->decode($token);

        $this->assertSame('jwks-user', $decoded->sub);
        Http::assertSentCount(1);

        if (is_resource($keyResource) || $keyResource instanceof \OpenSSLAsymmetricKey) {
            openssl_pkey_free($keyResource);
        }
    }
}
