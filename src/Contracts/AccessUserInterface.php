<?php

namespace Kroderdev\LaravelMicroserviceCore\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Contract for user models that expose roles and permissions.
 */
interface AccessUserInterface extends Authenticatable
{
    /** Load roles and permissions for the user */
    public function loadAccess(array $roles, array $permissions): void;

    /** Check if the user has the given role */
    public function hasRole(string $role): bool;

    /** Check if the user has the given permission */
    public function hasPermissionTo(string $permission): bool;

    /** Return all role names */
    public function getRoleNames(): array;

    /** Return all permissions */
    public function getPermissions(): array;
}