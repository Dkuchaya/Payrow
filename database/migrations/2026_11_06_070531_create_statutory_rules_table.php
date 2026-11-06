
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rules', function (Blueprint $table) {
            $table->id();

            // Parent statutory rule set
            $table->foreignId('statutory_rule_set_id')
                ->constrained('statutory_rule_sets')
                ->cascadeOnDelete();

            // Rule identification
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('description')->nullable();

            // Rule classification
            $table->string('rule_type', 50);
            // Examples: tax, pension, health, levy

            $table->string('applies_to', 30)->default('employee');
            // employee, employer, both

            // Calculation configuration
            $table->string('calculation_method', 50);
            // percentage, progressive, fixed_amount

            $table->string('earnings_basis', 100)->nullable();
            // Examples: basic_salary, gross_pay, taxable_emoluments

            $table->decimal('rate', 10, 6)->nullable();
            $table->decimal('fixed_amount', 15, 2)->nullable();

            // Earnings limits and contribution ceilings
            $table->decimal('minimum_earnings', 15, 2)->nullable();
            $table->decimal('maximum_earnings', 15, 2)->nullable();
            $table->decimal('contribution_ceiling', 15, 2)->nullable();

            // Additional configurable settings
            $table->json('settings')->nullable();

            // Administration
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            // A rule code must be unique within its rule set
            $table->unique(
                ['statutory_rule_set_id', 'code'],
                'statutory_rules_set_code_unique'
            );

            $table->index(['statutory_rule_set_id', 'is_active']);
            $table->index('rule_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rules');
    }
};
