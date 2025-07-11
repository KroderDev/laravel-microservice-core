<?php

namespace Tests\Rules;

use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Models\Model as ApiModel;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Tests\Services\FakeGatewayClient;

require_once __DIR__.'/../Services/FakeGatewayClient.php';

class RemoteRole extends ApiModel
{
    protected static string $endpoint = '/roles';

    protected $fillable = ['id'];
}

class ExistsRemoteTest extends TestCase
{
    protected FakeGatewayClient $gateway;

    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new FakeGatewayClient();
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);
    }

    /** @test */
    public function passes_when_all_ids_exist()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);
                if (in_array($uri, ['/roles/1', '/roles/2'])) {
                    $id = (int) substr($uri, 7);
                    return ['data' => ['id' => $id]];
                }

                return null;
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $validator = Validator::make(
            ['roles' => [1, 2]],
            ['roles.*' => ['exists_remote:' . RemoteRole::class . ',id']]
        );

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function rule_object_works_the_same()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);
                return ['data' => ['id' => (int) substr($uri, 7)]];
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $rule = new \Kroderdev\LaravelMicroserviceCore\Rules\ExistsRemote(RemoteRole::class);

        $validator = Validator::make(['role' => 1], ['role' => [$rule]]);

        $this->assertTrue($validator->passes());
    }

    /** @test */
    public function fails_when_any_id_is_missing()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);
                if ($uri === '/roles/1') {
                    return ['data' => ['id' => 1]];
                }

                return null;
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $validator = Validator::make(
            ['roles' => [1, 2]],
            ['roles.*' => ['exists_remote:' . RemoteRole::class . ',id']]
        );

        $this->assertFalse($validator->passes());
    }
}
