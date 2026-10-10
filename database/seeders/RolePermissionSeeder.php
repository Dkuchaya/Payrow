<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            // Companies
            'companies.view',
            'companies.create',
            'companies.update',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.assign_role',

            // Employees
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',

            // Leave
            'leave.view',
            'leave.manage',

            // Attendance
            'attendance.view',
            'attendance.manage',

            // Payroll
            'payroll.view',
            'payroll.manage',
            'payroll.approve',

            // Documents
            'documents.view',
            'documents.manage',

            // Reports
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate(
                $permission,
                'web'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $roles = [
            'company_admin',
            'hr_manager',
            'hr_officer',
            'payroll_manager',
            'accountant',
            'supervisor',
            'employee',
            'auditor',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate(
                $role,
                'web'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Company Admin
        |--------------------------------------------------------------------------
        */

        $companyAdmin = Role::findByName('company_admin', 'web');

        $companyAdmin->syncPermissions([
            'companies.view',
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
        ]);

        /*
        |--------------------------------------------------------------------------
        | HR Manager
        |--------------------------------------------------------------------------
        */

        Role::findByName('hr_manager', 'web')->syncPermissions([
            'users.view',
            'users.create',
            'users.update',

            'employees.view',
            'employees.create',
            'employees.update',

            'leave.view',
            'leave.manage',

            'attendance.view',
            'attendance.manage',

            'documents.view',
            'documents.manage',

            'reports.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | HR Officer
        |--------------------------------------------------------------------------
        */

        Role::findByName('hr_officer', 'web')->syncPermissions([
            'employees.view',
            'employees.create',
            'employees.update',

            'leave.view',
            'leave.manage',

            'attendance.view',
            'attendance.manage',

            'documents.view',
            'documents.manage',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Payroll Manager
        |--------------------------------------------------------------------------
        */

        Role::findByName('payroll_manager', 'web')->syncPermissions([
            'employees.view',

            'payroll.view',
            'payroll.manage',
            'payroll.approve',

            'reports.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Accountant
        |--------------------------------------------------------------------------
        */

        Role::findByName('accountant', 'web')->syncPermissions([
            'employees.view',

            'payroll.view',

            'reports.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Supervisor
        |--------------------------------------------------------------------------
        */

        Role::findByName('supervisor', 'web')->syncPermissions([
            'employees.view',

            'leave.view',
            'leave.manage',

            'attendance.view',
            'attendance.manage',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Employee
        |--------------------------------------------------------------------------
        */

        Role::findByName('employee', 'web')->syncPermissions([
            'employees.view',

            'leave.view',

            'attendance.view',

            'payroll.view',

            'documents.view',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Auditor
        |--------------------------------------------------------------------------
        */

        Role::findByName('auditor', 'web')->syncPermissions([
            'employees.view',

            'leave.view',

            'attendance.view',

            'payroll.view',

            'documents.view',

            'reports.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}