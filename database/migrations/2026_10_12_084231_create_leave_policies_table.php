<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnDelete();

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->decimal('entitlement_days', 8, 2)
                ->default(0);

            $table->string('accrual_method', 30)
                ->default('annual');

            $table->decimal('accrual_days_per_period', 8, 2)
                ->nullable();

            $table->boolean('carry_forward_allowed')
                ->default(false);

            $table->decimal('max_carry_forward_days', 8, 2)
                ->nullable();

            $table->unsignedInteger('min_notice_days')
                ->default(0);

            $table->decimal('max_consecutive_days', 8, 2)
                ->nullable();

            $table->boolean('allow_negative_balance')
                ->default(false);

            $table->boolean('requires_attachment')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->index(
                ['company_id', 'leave_type_id'],
                'leave_policies_company_type_index'
            );

            $table->index(
                ['company_id', 'effective_from'],
                'leave_policies_company_effective_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};