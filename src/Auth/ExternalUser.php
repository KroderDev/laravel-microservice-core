<?php

namespace Kroderdev\LaravelMicroserviceCore\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Kroderdev\LaravelMicroserviceCore\Contracts\AccessUserInterface;
use Kroderdev\LaravelMicroserviceCore\Traits\HasAccess;

class ExternalUser extends Authenticatable implements AccessUserInterface
{
    use Notifiable, HasAccess;
    
    protected $attributes = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct([]);
        $this->attributes = $attributes;
        $this->syncOriginal();
    }

    public function getAuthIdentifierName() { return 'id'; }
    public function getAuthIdentifier() { return $this->attributes[$this->getAuthIdentifierName()] ?? null; }
    public function getAuthPassword() { return null; }
}
