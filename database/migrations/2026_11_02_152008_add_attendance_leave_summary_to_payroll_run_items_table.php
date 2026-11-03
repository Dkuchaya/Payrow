<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->decimal('scheduled_days', 8, 2)->default(0)->after('basic_salary');
            $table->decimal('worked_days', 8, 2)->default(0)->after('scheduled_days');
            $table->decimal('paid_leave_days', 8, 2)->default(0)->after('worked_days');
            $table->decimal('unpaid_leave_days', 8, 2)->default(0)->after('paid_leave_days');
            $table->decimal('absent_days', 8, 2)->default(0)->after('unpaid_leave_days');

            $table->unsignedInteger('late_minutes')->default(0)->after('absent_days');
            $table->unsignedInteger('early_departure_minutes')->default(0)->after('late_minutes');
            $table->decimal('overtime_hours', 8, 2)->default(0)->after('early_departure_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
                Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_days',
                'worked_days',
                'paid_leave_days',
                'unpaid_leave_days',
                'absent_days',
                'late_minutes',
                'early_departure_minutes',
                'overtime_hours',
            ]);
        });
    }
};
