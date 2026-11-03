<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date')->nullable();

            $table->enum('frequency', [
                'weekly',
                'biweekly',
                'monthly',
            ])->default('monthly');

            $table->enum('status', [
                'open',
                'processing',
                'processed',
                'approved',
                'paid',
                'locked',
            ])->default('open');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique([
                'company_id',
                'start_date',
                'end_date',
            ]);

            $table->index([
                'company_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};