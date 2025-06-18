<?php

namespace Tests\Http;

use Orchestra\Testbench\TestCase;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;

class HealthCheckTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    /** @test */
    public function health_endpoint_returns_200()
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'app',
                     'environment',
                     'laravel',
                     'timestamp',
                 ]);
    }
}