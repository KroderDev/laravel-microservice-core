<?php

namespace Tests\Services;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Http;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Services\ApiGatewayClient;
use Orchestra\Testbench\TestCase;

class ApiGatewayClientTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    #[Test]
    public function post_sends_data_to_gateway()
    {
        Http::fake();
        app(ApiGatewayClient::class)->post('/items', ['name' => 'foo']);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'http://gateway.local/items'
                && $request['name'] === 'foo';
        });
    }

    #[Test]
    public function put_sends_data_to_gateway()
    {
        Http::fake();
        app(ApiGatewayClient::class)->put('/items/1', ['name' => 'bar']);

        Http::assertSent(function ($request) {
            return $request->method() === 'PUT'
                && $request->url() === 'http://gateway.local/items/1'
                && $request['name'] === 'bar';
        });
    }
}
