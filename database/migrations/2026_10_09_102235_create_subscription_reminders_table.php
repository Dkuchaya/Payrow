<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_reminders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_subscription_id')
                ->constrained('company_subscriptions')
                ->cascadeOnDelete();

            $table->unsignedInteger('days_before');

            $table->timestamp('sent_at');

            $table->timestamps();

            $table->unique(
                ['company_subscription_id', 'days_before'],
                'subscription_reminder_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_reminders');
    }
};