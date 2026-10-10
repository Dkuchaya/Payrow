<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CompanyRoleService
{
    public function assign(
        User $user,
        Company $company,
        string $role
    ): void {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($company->id);

        $roleModel = Role::findOrCreate(
            $role,
            'web'
        );

        $user->assignRole($roleModel);
    }

    public function remove(
        User $user,
        Company $company,
        string $role
    ): void {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($company->id);

        $user->removeRole($role);
    }

    public function hasRole(
        User $user,
        Company $company,
        string $role
    ): bool {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($company->id);

        return $user->hasRole($role);
    }
}