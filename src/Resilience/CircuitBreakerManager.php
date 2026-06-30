<?php

namespace Kroderdev\LaravelMicroserviceCore\Resilience;

use Illuminate\Contracts\Cache\Repository as CacheRepository;

class CircuitBreakerManager
{
    private CacheRepository $cache;

    public function __construct(CacheRepository $cache)
    {
        $this->cache = $cache;
    }

    public function for(string $serviceName): ?CircuitBreaker
    {
        $defaults = config('microservice.services.circuit_breaker_defaults', []);
        $serviceConfig = config("microservice.services.registry.{$serviceName}.circuit_breaker", []);

        $config = array_merge([
            'enabled' => false,
            'failure_threshold' => 5,
            'half_open_after' => 30,
            'success_threshold' => 2,
        ], $defaults, $serviceConfig);

        if (empty($config['enabled'])) {
            return null;
        }

        return new CircuitBreaker($serviceName, $config, $this->cache);
    }
}
