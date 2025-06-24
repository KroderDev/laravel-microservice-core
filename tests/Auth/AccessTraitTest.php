<?php

namespace Tests\Auth;

use Illuminate\Foundation\Auth\User;
use Kroderdev\LaravelMicroserviceCore\Traits\HasAccess;
use Orchestra\Testbench\TestCase;

class DefaultUser extends User
{
    use HasAccess;
    protected $fillable = ['id'];
}

class AccessTraitTest extends TestCase
{
    /** @test */
    public function trait_adds_access_methods_to_user()
    {
        $user = new DefaultUser(['id' => 1]);
        $user->loadAccess(['admin'], ['edit.posts']);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('guest'));
        $this->assertTrue($user->hasPermissionTo('edit.posts'));
        $this->assertEquals(['admin'], $user->getRoleNames());
        $this->assertEquals(['edit.posts'], $user->getPermissions());
    }
}