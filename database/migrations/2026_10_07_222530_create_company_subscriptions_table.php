<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->restrictOnDelete();

            $table->enum('status', [
                'trial',
                'active',
                'past_due',
                'suspended',
                'cancelled',
                'expired',
            ])->default('trial');

            $table->enum('billing_cycle', [
                'monthly',
                'annual',
            ])->default('monthly');

            $table->decimal('amount', 12, 2)->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index([
                'company_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};