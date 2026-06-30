<?php

namespace Kroderdev\LaravelMicroserviceCore\Resilience;

class RetryStrategy
{
    public static function create(array $config): callable
    {
        $config = array_merge([
            'backoff' => 'exponential',
            'base_delay' => 100,
            'max_delay' => 5000,
            'jitter' => true,
        ], $config);

        return function (int $attempt) use ($config): int {
            return static::delay($attempt, $config['backoff'], $config['base_delay'], $config['max_delay'], $config['jitter']);
        };
    }

    public static function delay(int $attempt, string $backoff, int $baseDelay, int $maxDelay, bool $jitter): int
    {
        $delay = match ($backoff) {
            'exponential' => $baseDelay * (2 ** ($attempt - 1)),
            'linear' => $baseDelay * $attempt,
            'fixed' => $baseDelay,
            default => $baseDelay,
        };

        $delay = min($delay, $maxDelay);

        if ($jitter && $delay > 0) {
            $variation = (int) ($delay * 0.25);

            $delay += random_int(-$variation, $variation);
        }

        return max(0, $delay);
    }
}
