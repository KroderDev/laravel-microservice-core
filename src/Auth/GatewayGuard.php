<?php

namespace Kroderdev\LaravelMicroserviceCore\Auth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Kroderdev\LaravelMicroserviceCore\Services\AuthServiceClient;
use Kroderdev\LaravelMicroserviceCore\Auth\ExternalUser;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;

class GatewayGuard extends SessionGuard
{
    protected AuthServiceClient $client;
    protected ?string $token = null;
    protected string $userModel;
    protected bool $loadAccess;

    /**
     * GatewayGuard constructor.
     *
     * @param string $name
     * @param UserProvider $provider
     * @param Session $session
     * @param Request $request
     * @param AuthServiceClient $client
     */
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

    /**
     * Get the currently authenticated user.
     *
     * @return mixed|null
     */
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
            //$this->fireAuthenticatedEvent($this->user); SetUser does this
        }

        return $this->user;
    }

    /**
     * Retrieve user data using the current JWT token.
     * If the token is invalid, attempt to refresh it.
     *
     * @return array|null
     */
    protected function retrieveUserData(): ?array
    {
        $token = $this->token;
        try {
            // Attempt to get the JWT public key from cache or load it from file
            $publicKey = Cache::remember('jwt_public_key', config('microservice.auth.jwt_cache_ttl', 3600), function () {
                Log::info('Attempting to load JWT public key for token validation.');
                $publicKey = file_get_contents(config('microservice.auth.jwt_public_key'));
                Log::info('JWT public key loaded.', ['publicKey' => substr($publicKey, 0, 30) . '...']);
                return $publicKey;
            });

            // Try to decode the token to check if it's valid
            JWT::decode($token, new Key($publicKey, config('microservice.auth.jwt_algorithm')));
            $valid = true;
        } catch (\Throwable $e) {
            // Log decode failure and mark token as invalid
            Log::warning('JWT decode failed in GatewayGuard', [
                'error' => $e->getMessage(),
                'token' => $token,
            ]);
            $valid = false;
        }

        if (! $valid) {
            try {
                // Attempt to refresh the token using the AuthServiceClient
                $response = $this->client->refresh($token);
                $token = $response['access_token'] ?? null;
                if (! $token) {
                    // If refresh fails, remove token from session and return null
                    $this->session->remove($this->getName());
                    return null;
                }
                // Update the token and session with the new token
                $this->token = $token;
                $this->updateSession($token);
            } catch (\Throwable $e) {
                // If refresh throws, remove token from session and return null
                $this->session->remove($this->getName());
                return null;
            }
        }

        try {
            // Retrieve user data from the AuthServiceClient using the valid token
            return $this->client->me($token);
        } catch (\Throwable $e) {
            // If user data retrieval fails, return null
            return null;
        }
    }

    /**
     * Update the session with the new token.
     *
     * @param string $token
     * @return void
     */
    protected function updateSession($token)
    {
        $this->session->put($this->getName(), $token);
        //$this->session->migrate(true); Conflicts with CSRF
    }

    /**
     * Get the current JWT token.
     *
     * @return string|null
     */
    public function token(): ?string
    {
        if (! $this->token) {
            $this->token = $this->session->get($this->getName());
        }

        return $this->token;
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     */
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

    /**
     * Log the user in using a JWT token and optional user data.
     *
     * @param string $token
     * @param array $userData
     * @param bool $remember
     * @return void
     */
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

    /**
     * Log the given user in.
     *
     * @param AuthenticatableContract $user
     * @param bool $remember
     * @return void
     */
    public function login(AuthenticatableContract $user, $remember = false)
    {
        $this->setUser($user);
        $this->fireLoginEvent($user, $remember);
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        $this->clearUserDataFromStorage();
        $this->token = null;
        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * Determine if the user was authenticated via "remember me".
     *
     * @return bool
     */
    public function viaRemember()
    {
        return false;
    }
}