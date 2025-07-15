<?php

namespace Tests\Models;

use Orchestra\Testbench\TestCase;

require_once __DIR__.'/../Services/FakeGatewayClient.php';
use Kroderdev\LaravelMicroserviceCore\Contracts\ApiGatewayClientInterface;
use Kroderdev\LaravelMicroserviceCore\Models\Model as ApiModel;
use Tests\Services\FakeGatewayClient;

class RemoteUser extends ApiModel
{
    protected static string $endpoint = '/users';

    protected $fillable = ['id', 'name'];
}

class FileModel extends ApiModel
{
    protected $fillable = ['id', 'type', 'name', 'size'];
}

class FolderModel extends ApiModel
{
    protected $fillable = ['id', 'name', 'children'];

    protected static array $apiRelations = [
        'children' => 'mapChildren',
    ];

    protected static function mapChildren(array $items)
    {
        return collect($items)->map(function (array $data) {
            if ($data['type'] === 'file') {
                return FileModel::fromApiResponse($data);
            }

            if ($data['type'] === 'folder') {
                return FolderModel::fromApiResponse($data);
            }

            throw new \UnexpectedValueException('Unknown child type: '.$data['type']);
        });
    }
}

class ApiModelTest extends TestCase
{
    protected FakeGatewayClient $gateway;

    protected function getPackageProviders($app)
    {
        return [\Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new FakeGatewayClient();
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);
    }

    /** @test */
    public function all_users_gateway()
    {
        // Simulate the gateway returning an array of users
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);

                // Simulate API returning an array of users
                return ['data' => [
                    ['id' => 1, 'name' => 'Alice'],
                    ['id' => 2, 'name' => 'Bob'],
                ]];
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $users = RemoteUser::all();
        $this->assertSame([['method' => 'GET', 'uri' => '/users', 'query' => []]], $this->gateway->getCalls());
        $this->assertCount(2, $users);
        $this->assertEquals('Alice', $users[0]->name);
        $this->assertEquals('Bob', $users[1]->name);
    }

    /** @test */
    public function find_users_gateway()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);
                if ($uri === '/users/5') {
                    return ['data' => ['id' => 5, 'name' => 'Eve']];
                }

                return null;
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $user = RemoteUser::find(5);
        $this->assertSame([['method' => 'GET', 'uri' => '/users/5', 'query' => []]], $this->gateway->getCalls());
        $this->assertInstanceOf(RemoteUser::class, $user);
        $this->assertEquals(5, $user->id);
        $this->assertEquals('Eve', $user->name);
    }

    /** @test */
    public function find_users_gateway_returns_null_when_not_found()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);

                return null; // Simulate not found
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $user = RemoteUser::find(999);
        $this->assertSame([['method' => 'GET', 'uri' => '/users/999', 'query' => []]], $this->gateway->getCalls());
        $this->assertNull($user);
    }

    /** @test */
    public function create_users_gateway()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function post(string $uri, array $data = [])
            {
                parent::post($uri, $data);

                // Ensure a valid array is always returned to avoid TypeError in create()
                return ['data' => ['id' => 10, 'name' => $data['name']]];
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $user = RemoteUser::create(['name' => 'John']);
        $this->assertSame([['method' => 'POST', 'uri' => '/users', 'data' => ['name' => 'John']]], $this->gateway->getCalls());
        $this->assertInstanceOf(RemoteUser::class, $user);
        $this->assertEquals('John', $user->name);
        $this->assertEquals(10, $user->id);
    }

    /** @test */
    public function all_users_gateway_handles_empty_response()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);

                return [];
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $users = RemoteUser::all();
        $this->assertSame([['method' => 'GET', 'uri' => '/users', 'query' => []]], $this->gateway->getCalls());
        $this->assertIsIterable($users);
        $this->assertCount(0, $users);
    }

    /** @test */
    public function all_users_gateway_handles_api_failure()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);

                return false; // Simulate API failure
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $this->expectException(\TypeError::class);
        RemoteUser::all();
    }

    /** @test */
    public function update_users_gateway()
    {
        $user = new RemoteUser(['id' => 9, 'name' => 'Old']);
        $user->exists = true;
        $user->name = 'New';
        $user->save();

        $this->assertSame([
            ['method' => 'PUT', 'uri' => '/users/9', 'data' => ['id' => 9, 'name' => 'New']],
        ], $this->gateway->getCalls());
    }

    /** @test */
    public function delete_users_gateway()
    {
        $user = new RemoteUser(['id' => 4]);
        $user->exists = true;
        $user->delete();

        $this->assertSame([
            ['method' => 'DELETE', 'uri' => '/users/4'],
        ], $this->gateway->getCalls());
    }

    /** @test */
    public function where_get_filters_results()
    {
        $this->gateway = new class () extends FakeGatewayClient {
            public function get(string $uri, array $query = [])
            {
                parent::get($uri, $query);

                return ['data' => [
                    ['id' => 7, 'name' => $query['name'] ?? ''],
                ]];
            }
        };
        $this->app->bind(ApiGatewayClientInterface::class, fn () => $this->gateway);

        $users = RemoteUser::where('name', 'Alice')->get();

        $this->assertSame([
            ['method' => 'GET', 'uri' => '/users', 'query' => ['name' => 'Alice']],
        ], $this->gateway->getCalls());
        $this->assertCount(1, $users);
        $this->assertEquals('Alice', $users[0]->name);
    }

    /** @test */
    public function from_api_response_maps_nested_relations()
    {
        $data = [
            'id' => 1,
            'name' => 'root',
            'type' => 'folder',
            'children' => [
                ['id' => 2, 'name' => 'file1.txt', 'type' => 'file', 'size' => 123],
                [
                    'id' => 3,
                    'name' => 'docs',
                    'type' => 'folder',
                    'children' => [
                        ['id' => 4, 'name' => 'file2.txt', 'type' => 'file', 'size' => 456],
                        [
                            'id' => 5,
                            'name' => 'deep',
                            'type' => 'folder',
                            'children' => [
                                ['id' => 6, 'name' => 'file3.txt', 'type' => 'file', 'size' => 789],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $folder = FolderModel::fromApiResponse($data);

        $this->assertEquals(1, $folder->id);
        $this->assertCount(2, $folder->children);
        $this->assertInstanceOf(FileModel::class, $folder->children[0]);
        $this->assertEquals(123, $folder->children[0]->size);
        $this->assertInstanceOf(FolderModel::class, $folder->children[1]);

        $subfolder = $folder->children[1];
        $this->assertCount(2, $subfolder->children);
        $this->assertInstanceOf(FileModel::class, $subfolder->children[0]);
        $this->assertInstanceOf(FolderModel::class, $subfolder->children[1]);

        $deep = $subfolder->children[1];
        $this->assertCount(1, $deep->children);
        $this->assertInstanceOf(FileModel::class, $deep->children[0]);
        $this->assertEquals('file3.txt', $deep->children[0]->name);
        $this->assertEquals(789, $deep->children[0]->size);
    }
}
