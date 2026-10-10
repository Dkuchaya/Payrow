<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_separation_clearances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_separation_id')
                ->constrained('employee_separations')
                ->cascadeOnDelete();

            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('responsible_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_required')->default(true);

            $table->enum('status', [
                'pending',
                'cleared',
                'rejected',
            ])->default('pending');

            $table->text('comments')->nullable();

            $table->foreignId('cleared_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cleared_at')->nullable();

            $table->timestamps();

                    $table->index(
                ['company_id', 'employee_separation_id'],
                'esc_company_separation_idx'
            );

                    $table->index(
                ['company_id', 'status'],
                'esc_company_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_separation_clearances');
    }
};