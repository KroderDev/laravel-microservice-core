<?php

namespace Tests\Http;

use PHPUnit\Framework\Attributes\Test;
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
        $app['config']->set('microservice.services.registry.gateway.url', 'http://gateway.test');
        $app['config']->set('microservice.services.registry.gateway.timeout', 5);
        $app['config']->set('microservice.services.registry.gateway.retries', 2);
        $app['config']->set('microservice.tracing.correlation.header', 'X-Correlation-ID');
    }

    #[Test]
    public function macro_passes_correlation_header()
    {
        Http::fake();

        Route::get('/macro', function () {
            Http::service('gateway')->get('/foo');

            return response()->json(['ok' => true]);
        });

        $this->withHeaders(['X-Correlation-ID' => 'corr-123'])->get('/macro')->assertOk();

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Correlation-ID', 'corr-123');
        });
    }

    #[Test]
    public function macro_without_request_has_no_header()
    {
        Http::fake();

        Http::service('gateway')->get('/foo');

        Http::assertSent(function ($request) {
            return ! $request->hasHeader('X-Correlation-ID');
        });
    }
}
