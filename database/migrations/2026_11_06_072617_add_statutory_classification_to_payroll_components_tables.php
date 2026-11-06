<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Main payroll component configuration
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->boolean('is_paye_taxable')->default(false);
            $table->boolean('is_napsa_pensionable')->default(false);
            $table->boolean('is_nhima_liable')->default(false);
            $table->boolean('is_sdl_liable')->default(false);
        });

        // Preserve statutory classifications in payroll snapshots
        Schema::table('payroll_run_item_components', function (Blueprint $table) {
            $table->boolean('is_paye_taxable')->default(false);
            $table->boolean('is_napsa_pensionable')->default(false);
            $table->boolean('is_nhima_liable')->default(false);
            $table->boolean('is_sdl_liable')->default(false);
        });

        // Preserve existing PAYE taxability settings
        DB::table('payroll_components')
            ->update([
                'is_paye_taxable' => DB::raw('is_taxable'),
            ]);

        DB::table('payroll_run_item_components')
            ->update([
                'is_paye_taxable' => DB::raw('is_taxable'),
            ]);
    }

    public function down(): void
    {
        Schema::table('payroll_run_item_components', function (Blueprint $table) {
            $table->dropColumn([
                'is_paye_taxable',
                'is_napsa_pensionable',
                'is_nhima_liable',
                'is_sdl_liable',
            ]);
        });

        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn([
                'is_paye_taxable',
                'is_napsa_pensionable',
                'is_nhima_liable',
                'is_sdl_liable',
            ]);
        });
    }
};
