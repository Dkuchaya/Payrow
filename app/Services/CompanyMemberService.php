<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class CompanyMemberService
{
    public function addUser(
        User $user,
        Company $company,
        string $role
    ): void {
        DB::transaction(function () use ($user, $company, $role) {

            $user->companies()->syncWithoutDetaching([
                $company->id => [
                    'is_active' => true,
                    'joined_at' => now(),
                ],
            ]);

            $this->setTeam($company);

            $user->assignRole($role);
        });
    }

    public function updateRole(
    User $user,
    Company $company,
    string $role
): void {
    DB::transaction(function () use ($user, $company, $role) {

        // User must belong to this company
        $membershipExists = $user->companies()
            ->where('companies.id', $company->id)
            ->wherePivot('is_active', true)
            ->exists();

        if (! $membershipExists) {
            throw new \RuntimeException(
                'User does not belong to this company.'
            );
        }

        // Set the company/team first
        $this->setTeam($company);

        // IMPORTANT:
        // Clear cached Spatie relationships after changing teams.
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        // Assign the role for THIS company/team only
        $user->syncRoles([$role]);

        // Clear again so subsequent authorization checks use
        // the role/permission data for the current team.
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
    });
}

    public function activate(
        User $user,
        Company $company
    ): void {
        $user->companies()->updateExistingPivot(
            $company->id,
            [
                'is_active' => true,
            ]
        );
    }

    public function deactivate(
        User $user,
        Company $company
    ): void {
        $user->companies()->updateExistingPivot(
            $company->id,
            [
                'is_active' => false,
            ]
        );
    }

    public function remove(
        User $user,
        Company $company
    ): void {
        DB::transaction(function () use ($user, $company) {

            $this->setTeam($company);

            $user->companies()->detach(
                $company->id
            );

            $user->syncRoles([]);
        });
    }

    public function setTeam(Company $company): void
    {
        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($company->id);
    }
}