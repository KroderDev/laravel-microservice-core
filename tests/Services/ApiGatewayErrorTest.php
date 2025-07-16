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

        Route::get('/gateway-error', function () {
            return app(ApiGatewayClient::class)->get('/fail');
        });
    }

    public static function statusProvider(): array
    {
        return [[200], [201], [400], [401], [419], [500]];
    }

    /**
     * @test
     * @dataProvider statusProvider
     */
    public function propagates_gateway_status_code(int $status)
    {
        Http::fake(['*' => Http::response(['message' => 'm'], $status)]);

        $response = $this->getJson('/gateway-error');
        $response->assertStatus($status);

        if ($status >= 400) {
            $response->assertJson(['error' => 'm']);
        }
    }

    /** @test */
    public function aborts_in_frontend_context()
    {
        Http::fake(['*' => Http::response(['message' => 'nope'], 401)]);

        $this->withoutExceptionHandling();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('nope');

        $this->get('/gateway-error');
    }

    /** @test */
    public function returns_json_in_service_context()
    {
        Http::fake(['*' => Http::response(['message' => 'bad'], 400)]);

        $this->getJson('/gateway-error')
            ->assertStatus(400)
            ->assertJson(['error' => 'bad']);
    }
}
