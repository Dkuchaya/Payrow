<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('phone')->nullable();

            $table->string('timezone')
                ->default('Africa/Lusaka');

            $table->string('date_format')
                ->default('d/m/Y');

            $table->string('language')
                ->default('en');

            $table->boolean('email_notifications')
                ->default(true);

            $table->boolean('system_notifications')
                ->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};