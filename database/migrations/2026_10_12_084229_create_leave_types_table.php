<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('code', 50);

            $table->text('description')->nullable();

            $table->boolean('is_paid')->default(true);

            $table->boolean('requires_balance')->default(true);

            $table->boolean('requires_attachment')->default(false);

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(
                ['company_id', 'code'],
                'leave_types_company_code_unique'
            );

            $table->index(
                ['company_id', 'is_active'],
                'leave_types_company_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};