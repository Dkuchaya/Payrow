<?php

namespace App\Console\Commands;

use App\Models\CompanySubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire active and trial subscriptions whose expiry dates have passed';

    public function handle(): int
    {
        $expiredActive = CompanySubscription::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->update([
                'status' => 'expired',
            ]);

        $expiredTrials = CompanySubscription::query()
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->update([
                'status' => 'expired',
            ]);

        $total = $expiredActive + $expiredTrials;

        $this->info("Expired {$total} subscription(s).");

        return self::SUCCESS;
    }
}