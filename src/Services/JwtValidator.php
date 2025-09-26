<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class JwtValidator
{
    public function decode(string $token): object
    {
        $verificationKey = $this->getVerificationKey($token);

        return JWT::decode($token, $verificationKey);
    }

    public function isValid(string $token): bool
    {
        try {
            $this->decode($token);

            return true;
        } catch (\Throwable $e) {
            Log::warning('JWT decode failed', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);

            return false;
        }
    }

    protected function getVerificationKey(string $token): Key
    {
        $algorithm = config('microservice.auth.jwt_algorithm', 'RS256');
        $cacheTtl = (int) config('microservice.auth.jwt_cache_ttl', 3600);
        $publicKeyPath = config('microservice.auth.jwt_public_key');
        $jwksUrl = config('microservice.auth.oidc.jwks_url');
        $kid = $this->extractKid($token);

        if ($publicKeyPath) {
            $publicKey = Cache::remember('jwt_public_key', $cacheTtl, function () use ($publicKeyPath) {
                Log::info('Loaded JWT public key for token validation', ['path' => $publicKeyPath]);

                $key = file_get_contents($publicKeyPath);
                if ($key === false) {
                    throw new RuntimeException('Unable to read JWT public key.');
                }

                return $key;
            });

            return new Key($publicKey, $algorithm);
        }

        if ($jwksUrl) {
            return $this->getKeyFromJwks($jwksUrl, $kid, $algorithm, $cacheTtl);
        }

        throw new RuntimeException('No JWT verification source configured.');
    }

    protected function getKeyFromJwks(string $jwksUrl, ?string $kid, string $algorithm, int $ttl): Key
    {
        $cacheKey = 'jwt_jwks:'.md5($jwksUrl);

        $jwks = Cache::remember($cacheKey, $ttl, function () use ($jwksUrl) {
            Log::info('Fetching JWKS for token validation', ['url' => $jwksUrl]);
            $response = Http::timeout(5)->acceptJson()->get($jwksUrl);
            if ($response->failed()) {
                throw new RuntimeException('Unable to download JWKS from identity provider.');
            }

            $data = $response->json();
            if (! is_array($data)) {
                throw new RuntimeException('Invalid JWKS response.');
            }

            return $data;
        });

        $keys = JWK::parseKeySet($jwks, $algorithm);

        if ($kid && isset($keys[$kid])) {
            return $keys[$kid];
        }

        if (! $kid && count($keys) === 1) {
            return array_values($keys)[0];
        }

        if ($kid) {
            Cache::forget($cacheKey);
            $jwks = Cache::remember($cacheKey, $ttl, function () use ($jwksUrl) {
                Log::info('Refreshing JWKS after missing kid', ['url' => $jwksUrl]);
                $response = Http::timeout(5)->acceptJson()->get($jwksUrl);
                if ($response->failed()) {
                    throw new RuntimeException('Unable to refresh JWKS from identity provider.');
                }

                $data = $response->json();
                if (! is_array($data)) {
                    throw new RuntimeException('Invalid JWKS response.');
                }

                return $data;
            });

            $keys = JWK::parseKeySet($jwks, $algorithm);
            if (isset($keys[$kid])) {
                return $keys[$kid];
            }
        }

        if (! empty($keys)) {
            return array_values($keys)[0];
        }

        throw new RuntimeException('Unable to resolve a verification key from JWKS.');
    }

    protected function extractKid(string $token): ?string
    {
        $parts = explode('.', $token);
        if (count($parts) < 2) {
            return null;
        }

        try {
            $header = json_decode($this->urlsafeB64Decode($parts[0]), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            Log::warning('Unable to decode JWT header', ['error' => $e->getMessage()]);

            return null;
        }

        return is_array($header) ? ($header['kid'] ?? null) : null;
    }

    protected function urlsafeB64Decode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padLength = 4 - $remainder;
            $input .= str_repeat('=', $padLength);
        }

        return base64_decode(strtr($input, '-_', '+/')) ?: '';
    }
}
