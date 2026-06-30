<?php

namespace Tests\Resilience;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Kroderdev\LaravelMicroserviceCore\Exceptions\CircuitBreakerOpenException;
use Kroderdev\LaravelMicroserviceCore\Exceptions\ServiceClientException;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Services\ServiceClient;
use PHPUnit\Framework\Attributes\Test;

class CircuitBreakerIntegrationTest extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    protected function configureService(array $circuitBreakerConfig = []): void
    {
        config()->set('microservice.services.registry.test', array_merge([
            'url' => 'http://test-service.local',
            'timeout' => 5,
            'retries' => 0,
            'circuit_breaker' => array_merge([
                'enabled' => true,
                'failure_threshold' => 2,
                'half_open_after' => 0,
                'success_threshold' => 1,
            ], $circuitBreakerConfig),
        ], []));
    }

    #[Test]
    public function does_not_reject_when_circuit_breaker_disabled()
    {
        Http::fake();

        $client = ServiceClient::to('gateway');
        $result = $client->get('/items');

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function rejects_requests_when_circuit_open()
    {
        $this->configureService();

        Http::fake([
            'http://test-service.local/*' => Http::sequence()
                ->push(['error' => 'down'], 503)
                ->push(['error' => 'down'], 503),
        ]);

        $client = ServiceClient::to('test');

        try {
            $client->get('/fail');
        } catch (ServiceClientException $e) {
        }

        try {
            $client->get('/fail');
        } catch (ServiceClientException $e) {
        }

        $this->expectException(CircuitBreakerOpenException::class);
        $client->get('/fail');
    }

    #[Test]
    public function recovers_in_half_open()
    {
        $this->configureService(['half_open_after' => 1]);

        Http::fake([
            'http://test-service.local/*' => Http::sequence()
                ->push(['error' => 'down'], 503)
                ->push(['error' => 'down'], 503)
                ->push(['ok' => true], 200),
        ]);

        $client = ServiceClient::to('test');

        try {
            $client->get('/fail');
        } catch (ServiceClientException $e) {
        }
        try {
            $client->get('/fail');
        } catch (ServiceClientException $e) {
        }

        sleep(1);

        $client = ServiceClient::to('test');

        $result = $client->get('/items');

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function does_not_trip_on_4xx()
    {
        $this->configureService();

        Http::fake([
            'http://test-service.local/*' => Http::sequence()
                ->push(['error' => 'not found'], 404)
                ->push(['error' => 'not found'], 404)
                ->push(['ok' => true], 200),
        ]);

        $client = ServiceClient::to('test');

        try {
            $client->get('/missing');
        } catch (ServiceClientException $e) {
        }

        try {
            $client->get('/missing');
        } catch (ServiceClientException $e) {
        }

        $client = ServiceClient::to('test');

        $result = $client->get('/items');

        $this->assertEquals(200, $result->getStatusCode());
    }

    #[Test]
    public function renders_circuit_breaker_open_as_503_json()
    {
        $this->configureService();

        Http::fake([
            'http://test-service.local/*' => Http::sequence()
                ->push(['error' => 'down'], 503)
                ->push(['error' => 'down'], 503),
        ]);

        Route::get('/cb-error', function () {
            $client = ServiceClient::to('test');

            try {
                $client->get('/fail');
            } catch (ServiceClientException $e) {
            }

            try {
                $client->get('/fail');
            } catch (ServiceClientException $e) {
            }

            $client->get('/fail');
        });

        $this->getJson('/cb-error')
            ->assertStatus(503)
            ->assertJson(['error' => 'Circuit breaker is open for service [test].']);
    }
}
