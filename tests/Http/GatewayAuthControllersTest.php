<?php

namespace Tests\Http;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Orchestra\Testbench\TestCase;
use Kroderdev\LaravelMicroserviceCore\Auth\GatewayGuard;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Providers\MicroserviceServiceProvider;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;
use Kroderdev\LaravelMicroserviceCore\Http\Auth\LoginController;
use Kroderdev\LaravelMicroserviceCore\Http\Auth\RegisterController;
use Kroderdev\LaravelMicroserviceCore\Http\Auth\LogoutController;
use Kroderdev\LaravelMicroserviceCore\Http\Auth\SocialiteController;

class FakeAuthServiceClient extends AuthServiceClient
{
    public function login(array $credentials): array
    {
        return ['access_token' => 'token_login', 'user' => ['id' => '1']];
    }

    public function register(array $data): array
    {
        return ['access_token' => 'token_register', 'user' => ['id' => '2']];
    }

    public function socialiteRedirect(string $provider): string
    {
        return 'https://oauth.test/redirect';
    }

    public function socialiteCallback(string $provider, string $code, string $state): array
    {
        return ['access_token' => 'token_socialite', 'user' => ['id' => '3']];
    }
}

class GatewayAuthControllersTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [MicroserviceServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(AuthServiceClient::class, fn () => new FakeAuthServiceClient());

        Auth::extend('gateway', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            return new GatewayGuard(
                $name,
                $provider,
                $app['session.store'],
                $app->make('request'),
                $app->make(AuthServiceClient::class)
            );
        });

        Config::set('auth.providers.users', [
            'driver' => 'eloquent',
            'model'  => ExternalUser::class,
        ]);
        Config::set('auth.guards.gateway', ['driver' => 'gateway', 'provider' => 'users']);

        Auth::guard('gateway')->setCookieJar($this->app['cookie']);

        Route::post('/login', LoginController::class);
        Route::post('/register', RegisterController::class);
        Route::post('/logout', LogoutController::class);
        Route::get('/socialite/{provider}/redirect', [SocialiteController::class, 'redirect']);
        Route::get('/socialite/{provider}/callback', [SocialiteController::class, 'callback']);
    }

    /** @test */
    public function login_controller_authenticates_user()
    {
        $response = $this->postJson('/login', ['email' => 'foo', 'password' => 'bar']);

        $response->assertOk()->assertJson(['user' => ['id' => '1']]);
        $this->assertEquals('token_login', Session::get(Auth::guard('gateway')->getName()));
    }

    /** @test */
    public function register_controller_logs_in_user()
    {
        $response = $this->postJson('/register', ['email' => 'foo', 'password' => 'bar']);

        $response->assertOk();
        $this->assertEquals('token_register', Session::get(Auth::guard('gateway')->getName()));
    }

    /** @test */
    public function logout_controller_clears_session()
    {
        Session::put(Auth::guard('gateway')->getName(), 'tok');

        $this->postJson('/logout')->assertOk();
        $this->assertNull(Session::get(Auth::guard('gateway')->getName()));
    }

    /** @test */
    public function socialite_redirect_returns_redirect()
    {
        $this->get('/socialite/github/redirect')->assertRedirect('https://oauth.test/redirect');
    }

    /** @test */
    public function socialite_callback_logs_in_user()
    {
        $this->get('/socialite/github/callback?code=a&state=b')->assertRedirect('/');
        $this->assertEquals('token_socialite', Session::get(Auth::guard('gateway')->getName()));
    }

    /** @test */
    public function login_controller_redirects_if_requested()
    {
        $this->post('/login?redirect=/home', ['email' => 'foo', 'password' => 'bar'])
            ->assertRedirect('/home');
    }

    /** @test */
    public function register_controller_redirects_if_requested()
    {
        $this->post('/register?redirect=/welcome', ['email' => 'foo', 'password' => 'bar'])
            ->assertRedirect('/welcome');
    }

    /** @test */
    public function logout_controller_redirects_if_requested()
    {
        Session::put(Auth::guard('gateway')->getName(), 'tok');
        $this->post('/logout?redirect=/bye')->assertRedirect('/bye');
    }

    /** @test */
    public function socialite_callback_honors_redirect_parameter()
    {
        $this->get('/socialite/github/callback?code=a&state=b&redirect=/foo')
            ->assertRedirect('/foo');
    }
}