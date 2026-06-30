<?php

namespace Tests\Resilience;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Kroderdev\LaravelMicroserviceCore\Exceptions\CircuitBreakerOpenException;
use Kroderdev\LaravelMicroserviceCore\Resilience\CircuitBreaker;
use PHPUnit\Framework\Attributes\Test;

class CircuitBreakerTest extends \Orchestra\Testbench\TestCase
{
    private CacheRepository $cache;

    private array $config = [
        'enabled' => true,
        'failure_threshold' => 3,
        'half_open_after' => 30,
        'success_threshold' => 2,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new CacheRepository(new ArrayStore());
    }

    private function makeBreaker(string $name = 'test', ?array $config = null): CircuitBreaker
    {
        return new CircuitBreaker($name, $config ?? $this->config, $this->cache);
    }

    #[Test]
    public function initial_state_is_closed()
    {
        $cb = $this->makeBreaker();

        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    #[Test]
    public function check_does_not_throw_when_closed()
    {
        $cb = $this->makeBreaker();

        $cb->check();

        $this->assertTrue(true);
    }

    #[Test]
    public function records_failures_but_stays_closed_below_threshold()
    {
        $cb = $this->makeBreaker();

        $cb->recordFailure();
        $cb->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    #[Test]
    public function opens_after_threshold_failures()
    {
        $cb = $this->makeBreaker();

        $cb->recordFailure();
        $cb->recordFailure();
        $cb->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $cb->getState());
    }

    #[Test]
    public function throws_when_open()
    {
        $cb = $this->makeBreaker();

        $cb->recordFailure();
        $cb->recordFailure();
        $cb->recordFailure();

        $this->expectException(CircuitBreakerOpenException::class);
        $cb->check();
    }

    #[Test]
    public function stays_open_with_zero_half_open_after()
    {
        $cb = $this->makeBreaker('test', array_merge($this->config, ['half_open_after' => 0]));

        $cb->recordFailure();
        $cb->recordFailure();
        $cb->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $cb->getState());

        $this->expectException(CircuitBreakerOpenException::class);
        $cb->check();
    }

    #[Test]
    public function transitions_to_half_open_after_timeout()
    {
        $cb = $this->makeBreaker('test', array_merge($this->config, ['half_open_after' => 1]));

        $cb->recordFailure();
        $cb->recordFailure();
        $cb->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $cb->getState());

        sleep(1);

        $cb->check();

        $this->assertEquals(CircuitBreaker::STATE_HALF_OPEN, $cb->getState());
    }

    #[Test]
    public function closes_after_success_threshold_in_half_open()
    {
        $cb = $this->makeBreaker('test', array_merge($this->config, [
            'failure_threshold' => 1,
            'half_open_after' => 1,
            'success_threshold' => 2,
        ]));

        $cb->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $cb->getState());

        sleep(1);

        $cb->check();

        $this->assertEquals(CircuitBreaker::STATE_HALF_OPEN, $cb->getState());

        $cb->recordSuccess();
        $cb->recordSuccess();

        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    #[Test]
    public function opens_on_failure_in_half_open()
    {
        $cb = $this->makeBreaker('test', array_merge($this->config, [
            'failure_threshold' => 1,
            'half_open_after' => 1,
        ]));

        $cb->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $cb->getState());

        sleep(1);

        $cb->check();

        $this->assertEquals(CircuitBreaker::STATE_HALF_OPEN, $cb->getState());

        $cb->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $cb->getState());
    }

    #[Test]
    public function success_in_closed_resets_failures()
    {
        $cb = $this->makeBreaker();

        $cb->recordFailure();
        $cb->recordFailure();
        $cb->recordSuccess();

        $cb->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $cb->getState());
    }

    #[Test]
    public function exception_message_includes_service_name()
    {
        $cb = $this->makeBreaker('payment-service');
        $cb->recordFailure();
        $cb->recordFailure();
        $cb->recordFailure();

        $this->expectException(CircuitBreakerOpenException::class);
        $this->expectExceptionMessage('Circuit breaker is open for service [payment-service].');

        $cb->check();
    }

    #[Test]
    public function independent_services_dont_interfere()
    {
        $a = $this->makeBreaker('service-a');
        $b = $this->makeBreaker('service-b');

        $a->recordFailure();
        $a->recordFailure();
        $a->recordFailure();

        $this->assertEquals(CircuitBreaker::STATE_OPEN, $a->getState());
        $this->assertEquals(CircuitBreaker::STATE_CLOSED, $b->getState());
    }
}
