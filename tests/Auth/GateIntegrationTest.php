<?php

namespace Tests\Auth;

use Illuminate\Support\Facades\Gate;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Orchestra\Testbench\TestCase;

class GateIntegrationTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    /** @test */
    public function gates_respect_roles_and_permissions()
    {
        $user = new ExternalUser(['id' => '1']);
        $user->loadAccess(['admin'], ['posts.view']);
        $this->be($user);

        $this->assertTrue(Gate::allows('posts.view'));
        $this->assertTrue(Gate::allows('permission:posts.view'));
        $this->assertTrue(Gate::allows('role:admin'));
        $this->assertFalse(Gate::allows('role:guest'));
        $this->assertFalse(Gate::allows('posts.edit'));
    }
}
