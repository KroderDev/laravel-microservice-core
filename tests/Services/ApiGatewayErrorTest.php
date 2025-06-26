<?php

namespace Tests\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClient;

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
