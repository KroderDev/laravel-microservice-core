<?php

namespace Kroderdev\LaravelMicroserviceCore\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class JwtValidator
{
    public function decode(string $token): object
    {
        $publicKey = $this->getPublicKey();

        return JWT::decode($token, new Key($publicKey, config('microservice.auth.jwt_algorithm')));
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

    protected function getPublicKey(): string
    {
        return Cache::remember('jwt_public_key', config('microservice.auth.jwt_cache_ttl', 3600), function () {
            $path = config('microservice.auth.jwt_public_key');
            Log::info('Loaded JWT public key for token validation', ['path' => $path]);

            return file_get_contents($path);
        });
    }
}
