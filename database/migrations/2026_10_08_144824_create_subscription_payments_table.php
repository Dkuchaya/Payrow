<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_subscription_id')
                ->constrained('company_subscriptions')
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->string('currency', 3)->default('ZMW');

            $table->date('payment_date');

            $table->string('payment_reference')->nullable();

            $table->string('payment_method')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'company_subscription_id',
                'payment_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};