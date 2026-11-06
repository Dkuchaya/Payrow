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
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->decimal('employer_napsa', 15, 2)->default(0)->after('net_pay');
            $table->decimal('employer_nhima', 15, 2)->default(0)->after('employer_napsa');
            $table->decimal('skills_development_levy', 15, 2)->default(0)->after('employer_nhima');
            $table->decimal('employer_statutory_contributions', 15, 2)->default(0)->after('skills_development_levy');
            $table->decimal('employer_total_cost', 15, 2)->default(0)->after('employer_statutory_contributions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn([
                'employer_napsa',
                'employer_nhima',
                'skills_development_levy',
                'employer_statutory_contributions',
                'employer_total_cost',
            ]);
        });
    }
};
