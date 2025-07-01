<?php

namespace Tests\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClient;
use Orchestra\Testbench\TestCase;

class ApiGatewayErrorTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['error' => 'unavailable'], 503)]);

        Route::get('/gateway-error', function () {
            return app(ApiGatewayClient::class)->get('/fail');
        });
    }

    /** @test */
    public function propagates_gateway_status_code()
    {
        $this->get('/gateway-error')
            ->assertStatus(503)
            ->assertJson(['error' => 'unavailable']);
    }
}
