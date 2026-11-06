
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rule_sets', function (Blueprint $table) {
            $table->id();

            // Country information
            $table->char('country_code', 2)->default('ZM');
            $table->string('country_name', 100)->default('Zambia');

            // Rule set identification
            $table->string('name');
            $table->unsignedSmallInteger('tax_year');
            $table->unsignedSmallInteger('version')->default(1);

            // Effective period
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            // Draft, active or archived
            $table->enum('status', [
                'draft',
                'active',
                'archived',
            ])->default('draft');

            // Administration
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('activated_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Prevent duplicate versions for the same country and tax year
            $table->unique(
                ['country_code', 'tax_year', 'version'],
                'statutory_rule_sets_country_year_version_unique'
            );

            $table->index(['country_code', 'status']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rule_sets');
    }
};
