<?php

namespace Kroderdev\LaravelMicroserviceCore\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;
use Kroderdev\LaravelMicroserviceCore\Traits\HasAccess;

class ExternalUser extends Authenticatable implements AccessUserInterface
{
    use HasAccess;
    use Notifiable;

    protected $guarded = [];

    protected array $claims = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct([]);
        $this->fillAttributes($attributes);
    }

    protected function fillAttributes(array $attributes): void
    {
        if (empty($attributes)) {
            return;
        }

        $this->claims = $attributes['token_claims'] ?? $attributes;

        if (isset($attributes['token_claims'])) {
            unset($attributes['token_claims']);
        }

        $this->forceFill($attributes);
        $this->syncOriginal();
    }

    public function setClaims(array $claims): void
    {
        $this->claims = $claims;
    }

    public function getClaims(): array
    {
        return $this->claims;
    }

    public function getAuthIdentifierName()
    {
        return config('microservice.auth.user_identifier_claim', 'id');
    }

    public function getAuthIdentifier()
    {
        $identifier = parent::getAuthIdentifier();

        if ($identifier === null && $this->getAuthIdentifierName() !== 'sub') {
            return $this->getAttribute('sub');
        }

        return $identifier;
    }

    public function getAuthPassword()
    {
        return null;
    }
}
