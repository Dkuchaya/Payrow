<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\LeaveBalanceService;
use Illuminate\Console\Command;

class AccrueLeaveBalances extends Command
{
    protected $signature = 'leave:accrue
                            {--company= : Process only one company}
                            {--year= : Leave year to process}
                            {--date= : Accrual date (Y-m-d)}';

    protected $description = 'Accrue employee leave balances according to company leave policies';

    public function handle(LeaveBalanceService $service): int
    {
        $year = (int) ($this->option('year') ?: now()->year);

        $asOf = $this->option('date')
            ? now()->parse($this->option('date'))
            : now();

        $query = Company::query()
            ->where('is_active', true);

        if ($companyId = $this->option('company')) {
            $query->whereKey($companyId);
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->warn('No active companies found.');
            return self::SUCCESS;
        }

        $total = 0;

        foreach ($companies as $company) {
            try {
                $processed = $service->accrueCompanyBalances(
                    $company,
                    $year,
                    $asOf
                );

                $total += $processed;

                $this->info(
                    "{$company->name}: {$processed} balance(s) processed."
                );
            } catch (\Throwable $e) {
                $this->error(
                    "{$company->name}: {$e->getMessage()}"
                );
            }
        }

        $this->newLine();

        $this->info(
            "Leave accrual completed. {$total} balance(s) processed."
        );

        return self::SUCCESS;
    }
}