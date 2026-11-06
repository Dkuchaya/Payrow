
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rule_audits', function (Blueprint $table) {
            $table->id();

            // Rule set associated with the change
            $table->foreignId('statutory_rule_set_id')
                ->nullable()
                ->constrained('statutory_rule_sets')
                ->nullOnDelete();

            // User who performed the action
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Entity being audited:
            // StatutoryRuleSet, StatutoryRule or StatutoryTaxBand
            $table->morphs('auditable');

            // Action performed
            $table->string('action', 50);
            // created, updated, activated, archived, deleted

            // Previous and new configurations
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Explanation of the change
            $table->text('description')->nullable();

            $table->timestamps();

            // Useful for retrieving audit history
            $table->index(
                ['statutory_rule_set_id', 'created_at'],
                'statutory_audits_rule_set_date_index'
            );

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rule_audits');
    }
};
