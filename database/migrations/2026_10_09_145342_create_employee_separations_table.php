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
       Schema::create('employee_separations', function (Blueprint $table) {
    $table->id();

    $table->foreignId('company_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('employee_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->enum('separation_type', [
        'resignation',
        'termination',
        'redundancy',
        'retirement',
        'end_of_contract',
        'dismissal',
        'death',
        'abscondment',
        'mutual_separation',
        'other',
    ]);

    $table->date('notice_date')->nullable();
    $table->date('effective_date');
    $table->date('last_working_date')->nullable();

    $table->text('reason')->nullable();
    $table->text('notes')->nullable();

    $table->enum('status', [
        'pending',
        'approved',
        'clearance',
        'final_payroll',
        'completed',
        'cancelled',
    ])->default('pending');

    $table->foreignId('approved_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamp('approved_at')->nullable();

    $table->foreignId('created_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

    $table->timestamps();

    $table->index(['company_id', 'employee_id']);
    $table->index(['company_id', 'status']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_separations');
    }
};
