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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('organisation_type', 100)
                ->nullable()
                ->after('country');

            $table->decimal('annual_turnover', 15, 2)
                ->nullable()
                ->after('organisation_type');

            $table->boolean('is_sdl_exempt')
                ->default(false)
                ->after('annual_turnover');

            $table->string('sdl_exemption_reason')
                ->nullable()
                ->after('is_sdl_exempt');

            $table->string('sdl_exemption_reference')
                ->nullable()
                ->after('sdl_exemption_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'organisation_type',
                'annual_turnover',
                'is_sdl_exempt',
                'sdl_exemption_reason',
                'sdl_exemption_reference',
            ]);
        });
    }
};
