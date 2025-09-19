<?php

namespace Tests\Http;

use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Orchestra\Testbench\TestCase;

class HealthCheckTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    /** @test */
    public function health_endpoint_returns_200()
    {
        $response = $this->get('/health');

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
