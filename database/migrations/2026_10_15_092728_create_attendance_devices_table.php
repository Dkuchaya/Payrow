<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('device_type', 50)->default('biometric');

            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 150)->nullable();

            $table->string('connection_type', 30)
                ->default('api');

            $table->string('ip_address', 45)->nullable();
            $table->unsignedInteger('port')->nullable();

            $table->string('api_url')->nullable();

            $table->text('username')->nullable();
            $table->text('password')->nullable();

            $table->string('timezone', 100)
                ->default('Africa/Lusaka');

            $table->string('sync_method', 30)
                ->default('pull');

            $table->timestamp('last_synced_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['company_id', 'is_active'],
                'attendance_devices_company_active_idx'
            );

            $table->unique(
                ['company_id', 'serial_number'],
                'attendance_devices_company_serial_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_devices');
    }
};