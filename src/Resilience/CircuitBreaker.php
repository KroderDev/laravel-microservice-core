<?php

namespace Kroderdev\LaravelMicroserviceCore\Resilience;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Kroderdev\LaravelMicroserviceCore\Exceptions\CircuitBreakerOpenException;

class CircuitBreaker
{
    public const STATE_CLOSED = 'closed';

    public const STATE_OPEN = 'open';

    public const STATE_HALF_OPEN = 'half_open';

    private string $serviceName;

    private array $config;

    private CacheRepository $cache;

    public function __construct(string $serviceName, array $config, CacheRepository $cache)
    {
        $this->serviceName = $serviceName;
        $this->config = $config;
        $this->cache = $cache;
    }

    public function check(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_OPEN) {
            if ($this->config['half_open_after'] > 0 && $this->elapsedSinceOpen() >= $this->config['half_open_after']) {
                $this->transitionTo(self::STATE_HALF_OPEN);

                return;
            }

            throw new CircuitBreakerOpenException($this->serviceName);
        }
    }

    public function recordSuccess(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $successes = $this->cache->increment($this->cacheKey('successes'));

            if ($successes >= $this->config['success_threshold']) {
                $this->transitionTo(self::STATE_CLOSED);
            }
        }

        if ($state === self::STATE_CLOSED) {
            $this->cache->forget($this->cacheKey('failures'));
        }
    }

    public function recordFailure(): void
    {
        $state = $this->getState();

        if ($state === self::STATE_HALF_OPEN) {
            $this->transitionTo(self::STATE_OPEN);

            return;
        }

        if ($state === self::STATE_CLOSED) {
            $failures = $this->cache->increment($this->cacheKey('failures'));

            if ($failures >= $this->config['failure_threshold']) {
                $this->transitionTo(self::STATE_OPEN);
            }
        }
    }

    public function getState(): string
    {
        return $this->cache->get($this->cacheKey('state'), self::STATE_CLOSED);
    }

    private function transitionTo(string $state): void
    {
        $now = (int) microtime(true);

        $this->cache->set($this->cacheKey('state'), $state);
        $this->cache->set($this->cacheKey('opened_at'), $state === self::STATE_OPEN ? $now : null);
        $this->cache->forget($this->cacheKey('failures'));
        $this->cache->forget($this->cacheKey('successes'));
    }

    private function elapsedSinceOpen(): int
    {
        $openedAt = $this->cache->get($this->cacheKey('opened_at'), 0);

        if (! $openedAt) {
            return PHP_INT_MAX;
        }

        return (int) microtime(true) - $openedAt;
    }

    private function cacheKey(string $suffix): string
    {
        return "circuit_breaker:{$this->serviceName}:{$suffix}";
    }
}
