<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */
            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | User Account
            |--------------------------------------------------------------------------
            */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Employee Identification
            |--------------------------------------------------------------------------
            */
            $table->string('employee_number');

            $table->string('national_id')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Personal Information
            |--------------------------------------------------------------------------
            */
            $table->string('first_name');

            $table->string('middle_name')
                ->nullable();

            $table->string('last_name');

            $table->string('preferred_name')
                ->nullable();

            $table->enum('gender', [
                'male',
                'female',
                'other',
            ])->nullable();

            $table->date('date_of_birth')
                ->nullable();

            $table->string('marital_status')
                ->nullable();

            $table->string('nationality')
                ->default('Zambian');

            /*
            |--------------------------------------------------------------------------
            | Contact Information
            |--------------------------------------------------------------------------
            */
            $table->string('email')
                ->nullable();

            $table->string('phone')
                ->nullable();

            $table->string('alternative_phone')
                ->nullable();

            $table->text('address')
                ->nullable();

            $table->string('city')
                ->nullable();

            $table->string('country')
                ->default('Zambia');

            /*
            |--------------------------------------------------------------------------
            | Employment Information
            |--------------------------------------------------------------------------
            */
            $table->date('hire_date')
                ->nullable();

            $table->date('probation_end_date')
                ->nullable();

            $table->date('confirmation_date')
                ->nullable();

            $table->date('contract_start_date')
                ->nullable();

            $table->date('contract_end_date')
                ->nullable();

            $table->enum('employment_type', [
                'permanent',
                'contract',
                'temporary',
                'casual',
                'intern',
                'part_time',
            ])->default('permanent');

            $table->enum('employment_status', [
                'active',
                'on_leave',
                'suspended',
                'separated',
            ])->default('active');

            /*
            |--------------------------------------------------------------------------
            | Organization
            |--------------------------------------------------------------------------
            */
            $table->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('position_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Payroll Information
            |--------------------------------------------------------------------------
            */
            $table->decimal('basic_salary', 15, 2)
                ->default(0);

            $table->string('salary_currency', 3)
                ->default('ZMW');

            $table->enum('pay_frequency', [
                'monthly',
                'weekly',
                'bi_weekly',
                'daily',
            ])->default('monthly');

            /*
            |--------------------------------------------------------------------------
            | Bank Information
            |--------------------------------------------------------------------------
            */
            $table->string('bank_name')
                ->nullable();

            $table->string('bank_branch')
                ->nullable();

            $table->string('bank_account_name')
                ->nullable();

            $table->string('bank_account_number')
                ->nullable();

            $table->string('bank_branch_code')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Statutory Information
            |--------------------------------------------------------------------------
            */
            $table->string('tpin')
                ->nullable();

            $table->string('napsa_number')
                ->nullable();

            $table->string('nhima_number')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Emergency Contact
            |--------------------------------------------------------------------------
            */
            $table->string('emergency_contact_name')
                ->nullable();

            $table->string('emergency_contact_relationship')
                ->nullable();

            $table->string('emergency_contact_phone')
                ->nullable();

            $table->text('emergency_contact_address')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Separation
            |--------------------------------------------------------------------------
            */
            $table->date('separation_date')
                ->nullable();

            $table->string('separation_type')
                ->nullable();

            $table->text('separation_reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Additional
            |--------------------------------------------------------------------------
            */
            $table->text('notes')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->unique([
                'company_id',
                'employee_number',
            ]);

            $table->index([
                'company_id',
                'employment_status',
            ]);

            $table->index([
                'company_id',
                'department_id',
            ]);

            $table->index([
                'company_id',
                'position_id',
            ]);

            $table->index('national_id');
            $table->index('email');
            $table->index('tpin');
            $table->index('napsa_number');
            $table->index('nhima_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};