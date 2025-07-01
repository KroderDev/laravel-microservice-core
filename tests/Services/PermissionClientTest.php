<?php

namespace Tests\Services;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;
use Kroderdev\LaravelMicroserviceCore\Traits\HasAccess;
use Orchestra\Testbench\TestCase;

require_once __DIR__.'/FakeGatewayClient.php';

class DummyUser extends User implements AccessUserInterface
{
    use HasAccess;

    protected $fillable = ['id'];
}

class PermissionsClientTest extends TestCase
{
    protected FakeGatewayClient $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);

                return new class () {
                    public function failed()
                    {
                        return false;
                    }

                    public function json()
                    {
                        return ['roles' => ['admin'], 'permissions' => ['edit.posts']];
                    }
                };
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);
        Cache::flush();
        $this->app['config']->set('microservice.permissions_endpoint', '/permissions');
    }

    protected function getPackageProviders($app)
    {
        return [\Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider::class];
    }

    /** @test */
    public function retrieves_access_for_any_user_model()
    {
        $client = new PermissionsClient($this->app->make(ApiGatewayClientInterface::class));
        $user = new DummyUser(['id' => 5]);

        $access = $client->getAccessFor($user);

        $this->assertEquals(['admin'], $access['roles']);
        $this->assertEquals(['edit.posts'], $access['permissions']);
        $this->assertSame('/auth/permissions/5', $this->gateway->getCalls()[0]['uri']);
    }
}
