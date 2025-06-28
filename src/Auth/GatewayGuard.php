<?php

namespace Kroderdev\LaravelMicroserviceCore\Auth;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;

class GatewayGuard extends SessionGuard
{
    protected AuthServiceClient $client;
    protected ?string $token = null;
    protected string $userModel;
    protected bool $loadAccess;

    public function __construct(
        string $name,
        UserProvider $provider,
        Session $session,
        Request $request,
        AuthServiceClient $client
    ) {
        parent::__construct($name, $provider, $session, $request);
        $this->client = $client;
        $this->userModel = config('microservice.gateway_guard.user_model', ExternalUser::class);
        $this->loadAccess = (bool) config('microservice.gateway_guard.load_access', true);
    }

    public function user()
    {
        if ($this->loggedOut) {
            return null;
        }

        if (! is_null($this->user)) {
            return $this->user;
        }

        $this->token = $this->session->get($this->getName());
        if (! $this->token) {
            return null;
        }

        $data = $this->retrieveUserData();
        if ($data) {
            $model = $this->userModel;
            $user = new $model($data);
            if ($this->loadAccess && $user instanceof AccessUserInterface) {
                $user->loadAccess($data['roles'] ?? [], $data['permissions'] ?? []);
            }
            $this->setUser($user);
            $this->fireAuthenticatedEvent($this->user);
        }

        return $this->user;
    }

    protected function retrieveUserData(): ?array
    {
        $token = $this->token;
        try {
            $publicKey = Cache::remember('jwt_public_key', config('microservice.auth.jwt_cache_ttl', 3600), function () {
                return file_get_contents(config('microservice.auth.jwt_public_key'));
            });
            JWT::decode($token, new Key($publicKey, config('microservice.auth.jwt_algorithm')));
            $valid = true;
        } catch (\Throwable $e) {
            $valid = false;
        }

        if (! $valid) {
            try {
                $response = $this->client->refresh($token);
                $token = $response['access_token'] ?? null;
                if (! $token) {
                    $this->session->remove($this->getName());
                    return null;
                }
                $this->token = $token;
                $this->updateSession($token);
            } catch (\Throwable $e) {
                $this->session->remove($this->getName());
                return null;
            }
        }

        try {
            return $this->client->me($token);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function updateSession($token)
    {
        $this->session->put($this->getName(), $token);
        $this->session->migrate(true);
    }

    public function attempt(array $credentials = [], $remember = false)
    {
        $response = $this->client->login($credentials);
        $token = $response['access_token'] ?? null;
        if (! $token) {
            return false;
        }

        $this->token = $token;
        $this->updateSession($token);
        $userData = $response['user'] ?? $this->client->me($token);
        $model = $this->userModel;
        $user = new $model($userData);
        if ($this->loadAccess && $user instanceof AccessUserInterface) {
            $user->loadAccess($userData['roles'] ?? [], $userData['permissions'] ?? []);
        }
        $this->setUser($user);
        $this->fireLoginEvent($this->user, $remember);

        return true;
    }

    public function loginWithToken(string $token, array $userData = [], $remember = false): void
    {
        $this->token = $token;
        $this->updateSession($token);

        if (empty($userData)) {
            $userData = $this->client->me($token);
        }

        $model = $this->userModel;
        $user = new $model($userData);

        if ($this->loadAccess && $user instanceof AccessUserInterface) {
            $user->loadAccess($userData['roles'] ?? [], $userData['permissions'] ?? []);
        }

        $this->setUser($user);
        $this->fireLoginEvent($user, $remember);
    }

    public function login(AuthenticatableContract $user, $remember = false)
    {
        $this->setUser($user);
        $this->fireLoginEvent($user, $remember);
    }

    public function logout()
    {
        $this->clearUserDataFromStorage();
        $this->token = null;
        $this->user = null;
        $this->loggedOut = true;
    }

    public function viaRemember()
    {
        return false;
    }
}