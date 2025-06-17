<?php

namespace Kroderdev\LaravelMicroserviceCore\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ExternalUser extends Authenticatable
{
    use Notifiable;
    
    protected $attributes = [];
    protected array $roles = [];
    protected array $permissions = [];

    public function __construct(array $attributes = [])
    {
        parent::__construct([]);
        $this->attributes = $attributes;
        $this->syncOriginal();
    }

    public function getAuthIdentifierName() { return 'id'; }
    public function getAuthIdentifier() { return $this->attributes[$this->getAuthIdentifierName()] ?? null; }
    public function getAuthPassword() { return null; }
    public function __get($key){ return $this->attributes[$key] ?? null; }

    // Permissions
    public function loadAccess(array $roles, array $permissions): void
    {
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    public function hasPermissionTo(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    public function getRoleNames(): array
    {
        return $this->roles;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }
}
