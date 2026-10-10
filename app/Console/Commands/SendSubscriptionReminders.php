<?php

namespace App\Console\Commands;

use App\Models\CompanySubscription;
use App\Models\SubscriptionReminder;
use App\Notifications\SubscriptionNotification;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Console\Command;

class SendSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:remind';

    protected $description = 'Send subscription expiry reminders';

    protected array $reminderDays = [
        30,
        14,
        7,
        3,
        1,
    ];

    public function handle(): int
    {
        $sent = 0;

        $subscriptions = CompanySubscription::query()
            ->with(['company', 'plan'])
            ->whereIn('status', ['active', 'trial'])
            ->get();

        foreach ($subscriptions as $subscription) {
            $expiryDate = $subscription->status === 'trial'
                ? $subscription->trial_ends_at
                : $subscription->ends_at;

            if (! $expiryDate || $expiryDate->isPast()) {
                continue;
            }

            $secondsRemaining = now()->diffInSeconds($expiryDate, false);

            if ($secondsRemaining <= 0) {
                continue;
            }

            $daysRemaining = (int) ceil($secondsRemaining / 86400);

            if (! in_array($daysRemaining, $this->reminderDays, true)) {
                continue;
            }

            $reminderCreated = SubscriptionReminder::query()->insertOrIgnore([
                'company_subscription_id' => $subscription->id,
                'days_before' => $daysRemaining,
                'sent_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($reminderCreated === 0) {
                continue;
            }

        app(PermissionRegistrar::class)
            ->setPermissionsTeamId($subscription->company_id);

        $admins = $subscription->company
            ->users()
            ->wherePivot('is_active', true)
            ->get()
            ->filter(fn ($user) => $user->hasRole('company_admin'));

        foreach ($admins as $admin) {
            $admin->notify(
                new SubscriptionNotification(
                    $subscription,
                    'renewal_reminder',
                    $daysRemaining
                )
            );
        }

            $sent++;

            $this->info(
                "Sent {$daysRemaining}-day reminder for {$subscription->company->name}."
            );
        }

        $this->info("Total reminder events sent: {$sent}.");

        return self::SUCCESS;
    }
}