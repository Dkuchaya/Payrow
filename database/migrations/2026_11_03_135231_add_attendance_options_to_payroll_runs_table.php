<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->boolean('apply_attendance_rules')
                ->default(false)
                ->after('status');

            $table->boolean('deduct_unpaid_absence')
                ->default(false)
                ->after('apply_attendance_rules');

            $table->boolean('deduct_unpaid_leave')
                ->default(false)
                ->after('deduct_unpaid_absence');

            $table->boolean('deduct_late_time')
                ->default(false)
                ->after('deduct_unpaid_leave');

            $table->boolean('deduct_early_departure')
                ->default(false)
                ->after('deduct_late_time');

            $table->boolean('pay_overtime')
                ->default(false)
                ->after('deduct_early_departure');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn([
                'apply_attendance_rules',
                'deduct_unpaid_absence',
                'deduct_unpaid_leave',
                'deduct_late_time',
                'deduct_early_departure',
                'pay_overtime',
            ]);
        });
    }
};