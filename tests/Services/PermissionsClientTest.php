<?php

namespace Tests\Services;

use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Cache;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;
use Kroderdev\LaravelMicroserviceCore\Contracts\ServiceClientInterface;
use Kroderdev\LaravelMicroserviceCore\Services\PermissionsClient;
use Kroderdev\LaravelMicroserviceCore\Traits\HasAccess;
use Orchestra\Testbench\TestCase;

require_once __DIR__.'/FakeServiceClient.php';

class DummyUser extends User implements AccessUserInterface
{
    use HasAccess;

    protected $fillable = ['id'];
}

class PermissionsClientTest extends TestCase
{
    protected FakeServiceClient $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new class () extends FakeServiceClient {
            public function get(string $uri, array $query = []): mixed
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
        $this->app->bind(ServiceClientInterface::class, fn () => $this->gateway);
        Cache::flush();
        $this->app['config']->set('microservice.permissions_endpoint', '/permissions');
    }

    protected function getPackageProviders($app)
    {
        return [\Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider::class];
    }

    #[Test]
    public function retrieves_access_for_any_user_model()
    {
        $client = new PermissionsClient($this->app->make(ServiceClientInterface::class));
        $user = new DummyUser(['id' => 5]);

        $access = $client->getAccessFor($user);

        $this->assertEquals(['admin'], $access['roles']);
        $this->assertEquals(['edit.posts'], $access['permissions']);
        $this->assertSame('/permissions/5', $this->gateway->getCalls()[0]['uri']);
    }
}
