<?php

namespace Tests\Middleware;

use Illuminate\Support\Facades\Route;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\CorrelationId;
use Orchestra\Testbench\TestCase;

class CorrelationIdTest extends TestCase
{
    /** @test */
    public function it_generates_default_length_correlation_id()
    {
        $header = 'X-Correlation-ID';
        $length = 36;
        config()->set('microservice.correlation.header', $header);
        config()->set('microservice.correlation.length', $length);

        Route::middleware(CorrelationId::class)->get('/correlation-default', fn () => response()->json(['ok' => true]));

        $response = $this->get('/correlation-default');

        $this->assertSame($length, strlen($response->headers->get($header)));
    }

    /** @test */
    public function it_generates_configured_length_correlation_id()
    {
        $header = 'X-Correlation-ID';
        $length = 20;
        config()->set('microservice.correlation.header', $header);
        config()->set('microservice.correlation.length', $length);

        Route::middleware(CorrelationId::class)->get('/correlation-custom', fn () => response()->json(['ok' => true]));

        $response = $this->get('/correlation-custom');

        $this->assertSame($length, strlen($response->headers->get($header)));
    }
}
