<?php

namespace Tests\Http;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class HttpMacrosTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('services.api_gateway.url', 'http://gateway.test');
        $app['config']->set('microservice.correlation.header', 'X-Correlation-ID');
    }

    /** @test */
    public function macro_passes_correlation_header()
    {
        Http::fake();

        Route::get('/macro', function () {
            Http::apiGateway()->get('/foo');
            return response()->json(['ok' => true]);
        });

        $this->withHeaders(['X-Correlation-ID' => 'corr-123'])->get('/macro')->assertOk();

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Correlation-ID', 'corr-123');
        });
    }

    /** @test */
    public function macro_without_request_has_no_header()
    {
        Http::fake();

        Http::apiGateway()->get('/foo');

        Http::assertSent(function ($request) {
            return !$request->hasHeader('X-Correlation-ID');
        });
    }
}