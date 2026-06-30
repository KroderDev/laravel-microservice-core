<?php

namespace Tests\Resilience;

use Kroderdev\LaravelMicroserviceCore\Resilience\RetryStrategy;
use PHPUnit\Framework\Attributes\Test;

class RetryStrategyTest extends \Orchestra\Testbench\TestCase
{
    #[Test]
    public function fixed_backoff_returns_constant_delay()
    {
        $callback = RetryStrategy::create(['backoff' => 'fixed', 'base_delay' => 100, 'jitter' => false]);

        $this->assertEquals(100, $callback(1));
        $this->assertEquals(100, $callback(3));
        $this->assertEquals(100, $callback(5));
    }

    #[Test]
    public function exponential_backoff_grows()
    {
        $callback = RetryStrategy::create(['backoff' => 'exponential', 'base_delay' => 100, 'jitter' => false]);

        $this->assertEquals(100, $callback(1));
        $this->assertEquals(200, $callback(2));
        $this->assertEquals(400, $callback(3));
        $this->assertEquals(800, $callback(4));
    }

    #[Test]
    public function exponential_capped_at_max_delay()
    {
        $callback = RetryStrategy::create(['backoff' => 'exponential', 'base_delay' => 100, 'max_delay' => 500, 'jitter' => false]);

        $this->assertEquals(100, $callback(1));
        $this->assertEquals(200, $callback(2));
        $this->assertEquals(400, $callback(3));
        $this->assertEquals(500, $callback(4));
        $this->assertEquals(500, $callback(5));
    }

    #[Test]
    public function linear_backoff_increases_linearly()
    {
        $callback = RetryStrategy::create(['backoff' => 'linear', 'base_delay' => 100, 'jitter' => false]);

        $this->assertEquals(100, $callback(1));
        $this->assertEquals(200, $callback(2));
        $this->assertEquals(300, $callback(3));
    }

    #[Test]
    public function jitter_stays_within_bounds()
    {
        $baseDelay = 100;
        $callback = RetryStrategy::create(['backoff' => 'fixed', 'base_delay' => $baseDelay, 'jitter' => true]);

        for ($i = 0; $i < 100; $i++) {
            $delay = $callback(1);
            $this->assertGreaterThanOrEqual($baseDelay * 0.75, $delay);
            $this->assertLessThanOrEqual($baseDelay * 1.25, $delay);
        }
    }

    #[Test]
    public function defaults_to_exponential_with_jitter()
    {
        $callback = RetryStrategy::create([]);

        $this->assertIsCallable($callback);

        $delay = $callback(1);
        $this->assertGreaterThan(0, $delay);
        $this->assertLessThanOrEqual(150, $delay);
    }
}
