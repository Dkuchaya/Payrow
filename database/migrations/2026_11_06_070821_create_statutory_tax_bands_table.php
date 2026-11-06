
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_tax_bands', function (Blueprint $table) {
            $table->id();

            // Link to the PAYE statutory rule
            $table->foreignId('statutory_rule_id')
                ->constrained('statutory_rules')
                ->cascadeOnDelete();

            // Tax band identification
            $table->string('name', 100);

            // Taxable income boundaries
            $table->decimal('lower_limit', 15, 2)->default(0);
            $table->decimal('upper_limit', 15, 2)->nullable();

            // Marginal tax rate (e.g. 20.000000 means 20%)
            $table->decimal('tax_rate', 10, 6)->default(0);

            // Tax accumulated before entering this band
            $table->decimal('base_tax', 15, 2)->default(0);

            // Ordering of tax bands
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(
                ['statutory_rule_id', 'sort_order'],
                'statutory_tax_bands_rule_order_unique'
            );

            $table->index(['statutory_rule_id', 'lower_limit']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_tax_bands');
    }
};
