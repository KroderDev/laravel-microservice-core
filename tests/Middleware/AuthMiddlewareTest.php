<?php

namespace Tests\Middleware;

use Illuminate\Support\Facades\Route;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\RoleMiddleware;
use Kroderdev\LaravelMicroserviceCore\Http\Middleware\PermissionMiddleware;
use Orchestra\Testbench\TestCase;

class AuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register middleware aliases
        $this->app['router']->aliasMiddleware('role', RoleMiddleware::class);
        $this->app['router']->aliasMiddleware('permission', PermissionMiddleware::class);

        // Routes for testing
        Route::middleware('role:admin')->get('/role-protected', fn () => response()->json(['ok' => true]));
        Route::middleware('permission:edit.posts')->get('/permission-protected', fn () => response()->json(['ok' => true]));
    }

    /** @test */
    public function role_middleware_allows_user_with_required_role()
    {
        $user = new ExternalUser(['id' => 'user-1']);
        $user->loadAccess(['admin'], []);
        $this->actingAs($user);

        $this->get('/role-protected')->assertOk()->assertJson(['ok' => true]);
    }

    /** @test */
    public function role_middleware_blocks_user_without_role()
    {
        $user = new ExternalUser(['id' => 'user-1']);
        $user->loadAccess(['tester'], []);
        $this->actingAs($user);

        $this->get('/role-protected')->assertStatus(403);
    }

    /** @test */
    public function permission_middleware_allows_user_with_permission()
    {
        $user = new ExternalUser(['id' => 'user-1']);
        $user->loadAccess([], ['edit.posts']);
        $this->actingAs($user);

        $this->get('/permission-protected')->assertOk()->assertJson(['ok' => true]);
    }

    /** @test */
    public function permission_middleware_blocks_user_without_permission()
    {
        $user = new ExternalUser(['id' => 'user-1']);
        $user->loadAccess([], ['view.posts']);
        $this->actingAs($user);

        $this->get('/permission-protected')->assertStatus(403);
    }
}