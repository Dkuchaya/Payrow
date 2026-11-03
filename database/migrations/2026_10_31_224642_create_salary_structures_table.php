<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->restrictOnDelete();

            $table->decimal('basic_salary', 15, 2)->default(0);

            $table->string('currency', 3)->default('ZMW');

            $table->enum('pay_frequency', [
                'weekly',
                'biweekly',
                'monthly',
            ])->default('monthly');

            $table->date('effective_from');

            $table->date('effective_to')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'status']);

            $table->unique([
                'employee_id',
                'effective_from',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};