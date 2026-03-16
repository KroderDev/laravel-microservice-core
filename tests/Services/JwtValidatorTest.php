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
