<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        // Clear Spatie permission cache
        $registrar->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            'companies.view',
            'companies.create',
            'companies.update',

            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign_role',

            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',

            'leave.view',
            'leave.manage',

            'attendance.view',
            'attendance.manage',

            'payroll.view',
            'payroll.manage',
            'payroll.approve',

            'documents.view',
            'documents.manage',

            'reports.view',
        ];

        // Permissions are global; roles are company/team scoped.
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        /*
        |--------------------------------------------------------------------------
        | Role Permissions
        |--------------------------------------------------------------------------
        */

        $rolePermissions = [

            'company_admin' => [
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'users.assign_role',

                'employees.view',
                'employees.create',
                'employees.update',
                'employees.delete',

                'leave.view',
                'leave.manage',

                'attendance.view',
                'attendance.manage',

                'payroll.view',
                'payroll.manage',
                'payroll.approve',

                'documents.view',
                'documents.manage',

                'reports.view',
            ],

            'hr_manager' => [
                'users.view',
                'employees.view',
                'employees.create',
                'employees.update',
                'employees.delete',

                'leave.view',
                'leave.manage',

                'attendance.view',
                'attendance.manage',

                'documents.view',
                'documents.manage',

                'reports.view',
            ],

                'hr_officer' => [
                'employees.view',
                'employees.create',
                'employees.update',

                'leave.view',
                'leave.manage',

                'attendance.view',
                'attendance.manage',

                'documents.view',
                'documents.manage',
            ],

            'payroll_manager' => [
                'employees.view',

                'payroll.view',
                'payroll.manage',
                'payroll.approve',

                'reports.view',
            ],

            'accountant' => [
                'employees.view',

                'payroll.view',
                'payroll.manage',

                'reports.view',
            ],

            'supervisor' => [
                'employees.view',
                'leave.view',
                'attendance.view',
                'attendance.manage',
            ],

            'employee' => [
                'employees.view',
                'leave.view',
                'attendance.view',
                'documents.view',
            ],

            'auditor' => [
                'employees.view',
                'payroll.view',
                'leave.view',
                'attendance.view',
                'documents.view',
                'reports.view',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Create Roles For Every Active Company
        |--------------------------------------------------------------------------
        */

        $companies = Company::where('is_active', true)->get();

        foreach ($companies as $company) {

            // Set current Spatie team/company.
            $registrar->setPermissionsTeamId($company->id);

            foreach ($rolePermissions as $roleName => $permissionNames) {

                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'team_id' => $company->id,
                ]);

                $role->syncPermissions($permissionNames);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Old Global Company Roles
        |--------------------------------------------------------------------------
        |
        | We only remove global roles AFTER company-scoped roles have been
        | created above.
        |
        */

        Role::whereNull('team_id')
            ->whereIn('name', array_keys($rolePermissions))
            ->delete();

        // Reset current team.
        $registrar->setPermissionsTeamId(null);

        // Clear cache again.
        $registrar->forgetCachedPermissions();
    }
}