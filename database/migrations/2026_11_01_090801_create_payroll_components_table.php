<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code');

            $table->enum('type', [
                'allowance',
                'deduction',
            ]);

            $table->enum('calculation_type', [
                'fixed',
                'percentage',
            ])->default('fixed');

            $table->decimal('default_amount', 15, 2)->default(0);

            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};