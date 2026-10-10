<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            [
                'email' => 'admin@payrow.test',
            ],
            [
                'name' => 'PAYROW Super Admin',
                'password' => Hash::make('ChangeMe123!'),
            ]
        );

        $user->update([
            'is_super_admin' => true,
        ]);
    }
}