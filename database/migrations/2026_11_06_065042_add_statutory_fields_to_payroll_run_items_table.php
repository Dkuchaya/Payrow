<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            // Employee statutory deductions
            $table->decimal('paye', 15, 2)->default(0);
            $table->decimal('employee_napsa', 15, 2)->default(0);
            $table->decimal('employee_nhima', 15, 2)->default(0);

            // Employer statutory contributions
            $table->decimal('employer_napsa', 15, 2)->default(0);
            $table->decimal('employer_nhima', 15, 2)->default(0);
            $table->decimal('skills_development_levy', 15, 2)->default(0);

            // Statutory totals
            $table->decimal('employee_statutory_deductions', 15, 2)->default(0);
            $table->decimal('employer_statutory_contributions', 15, 2)->default(0);
            $table->decimal('employer_total_cost', 15, 2)->default(0);

            // Historical statutory calculation details
            $table->json('statutory_breakdown')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_run_items', function (Blueprint $table) {
            $table->dropColumn([
                'paye',
                'employee_napsa',
                'employee_nhima',
                'employer_napsa',
                'employer_nhima',
                'skills_development_levy',
                'employee_statutory_deductions',
                'employer_statutory_contributions',
                'employer_total_cost',
                'statutory_breakdown',
            ]);
        });
    }
};