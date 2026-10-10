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

            $this->setTeam($company);

            $user->syncRoles([$role]);
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