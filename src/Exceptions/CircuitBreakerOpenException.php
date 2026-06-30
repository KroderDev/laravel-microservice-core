<?php

namespace Kroderdev\LaravelMicroserviceCore\Exceptions;

use RuntimeException;

class CircuitBreakerOpenException extends RuntimeException
{
    private string $serviceName;

    public function __construct(string $serviceName)
    {
        $this->serviceName = $serviceName;

        parent::__construct("Circuit breaker is open for service [{$serviceName}].");
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
