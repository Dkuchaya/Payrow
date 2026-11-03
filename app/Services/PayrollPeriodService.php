<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PayrollPeriod;
use Illuminate\Validation\ValidationException;

class PayrollPeriodService
{
    public function create(
        Company $company,
        array $data
    ): PayrollPeriod {
        $this->validateDates($data);

        // Prevent duplicate payroll periods for the same company
        $exists = PayrollPeriod::query()
            ->where('company_id', $company->id)
            ->whereDate('start_date', $data['start_date'])
            ->whereDate('end_date', $data['end_date'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'start_date' => 'A payroll period already exists for these dates.',
            ]);
        }

        return PayrollPeriod::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'pay_date' => $data['pay_date'] ?? null,
            'frequency' => $data['frequency'] ?? 'monthly',
            'status' => 'open',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(
        PayrollPeriod $period,
        array $data
    ): PayrollPeriod {
        if ($period->status !== 'open') {
            throw ValidationException::withMessages([
                'period' => 'Only open payroll periods can be edited.',
            ]);
        }

        $this->validateDates($data);

        // Prevent duplicate payroll periods when changing dates.
        // Exclude the current period from the check.
        $exists = PayrollPeriod::query()
            ->where('company_id', $period->company_id)
            ->whereDate('start_date', $data['start_date'])
            ->whereDate('end_date', $data['end_date'])
            ->where('id', '!=', $period->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'start_date' => 'Another payroll period already exists for these dates.',
            ]);
        }

        $period->update([
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'pay_date' => $data['pay_date'] ?? null,
            'frequency' => $data['frequency'] ?? 'monthly',
            'notes' => $data['notes'] ?? null,
        ]);

        return $period->fresh();
    }

    public function markProcessing(PayrollPeriod $period): PayrollPeriod
    {
        $this->ensureStatus($period, 'open');

        $period->update([
            'status' => 'processing',
        ]);

        return $period->fresh();
    }

    public function markProcessed(PayrollPeriod $period): PayrollPeriod
    {
        $this->ensureStatus($period, 'processing');

        $period->update([
            'status' => 'processed',
        ]);

        return $period->fresh();
    }

    public function approve(PayrollPeriod $period): PayrollPeriod
    {
        $this->ensureStatus($period, 'processed');

        $period->update([
            'status' => 'approved',
        ]);

        return $period->fresh();
    }

    public function markPaid(PayrollPeriod $period): PayrollPeriod
    {
        $this->ensureStatus($period, 'approved');

        $period->update([
            'status' => 'paid',
        ]);

        return $period->fresh();
    }

    public function lock(PayrollPeriod $period): PayrollPeriod
    {
        $this->ensureStatus($period, 'paid');

        $period->update([
            'status' => 'locked',
        ]);

        return $period->fresh();
    }

    protected function validateDates(array $data): void
    {
        if (
            isset($data['start_date'], $data['end_date']) &&
            $data['start_date'] > $data['end_date']
        ) {
            throw ValidationException::withMessages([
                'end_date' => 'The end date must be on or after the start date.',
            ]);
        }

        if (
            isset($data['pay_date'], $data['end_date']) &&
            $data['pay_date'] < $data['end_date']
        ) {
            throw ValidationException::withMessages([
                'pay_date' => 'The pay date must be on or after the payroll period end date.',
            ]);
        }
    }

    protected function ensureStatus(
        PayrollPeriod $period,
        string $expectedStatus
    ): void {
        if ($period->status !== $expectedStatus) {
            throw ValidationException::withMessages([
                'period' => "Payroll period must be {$expectedStatus} before this action.",
            ]);
        }
    }
}
