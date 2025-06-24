<?php

namespace Kroderdev\LaravelMicroserviceCore\Traits;

trait HasAccess
{
    protected array $roles = [];
    protected array $permissions = [];

    /**
     * Load roles and permissions for the user.
     */
    public function loadAccess(array $roles, array $permissions): void
    {
        $this->roles = $roles;
        $this->permissions = $permissions;
    }

    /**
     * Determine if the user has the given role.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles);
    }

    /**
     * Determine if the user has the given permission.
     */
    public function hasPermissionTo(string $permission): bool
    {
        return in_array($permission, $this->permissions);
    }

    /**
     * Get all role names for the user.
     */
    public function getRoleNames(): array
    {
        return $this->roles;
    }

    /**
     * Get all permissions for the user.
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}