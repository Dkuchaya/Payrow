<?php

namespace App\Notifications;

use App\Models\CompanySubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public CompanySubscription $subscription,
        public string $event,
        public ?int $daysRemaining = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $company = $this->subscription->company;
        $plan = $this->subscription->plan;

        $message = match ($this->event) {
            'assigned' => "A {$plan->name} subscription has been assigned to {$company->name}.",
            'renewed' => "The {$plan->name} subscription for {$company->name} has been renewed.",
            'suspended' => "The subscription for {$company->name} has been suspended.",
            'cancelled' => "The subscription for {$company->name} has been cancelled.",
            'renewal_reminder' => "The {$plan->name} subscription for {$company->name} expires in {$this->daysRemaining} day(s).",
            default => "There has been an update to the subscription for {$company->name}.",
        };

        return (new MailMessage)
            ->subject('PAYROW Subscription Notification')
            ->greeting('Hello,')
            ->line($message)
            ->action(
                'View Subscription',
                route('company-subscriptions.show', $this->subscription->id)
            )
            ->line('This notification was sent by PAYROW.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'company_id' => $this->subscription->company_id,
            'company_name' => $this->subscription->company->name,
            'plan_name' => $this->subscription->plan->name,
            'event' => $this->event,
            'days_remaining' => $this->daysRemaining,
        ];
    }
}