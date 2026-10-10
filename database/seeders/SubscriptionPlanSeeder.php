<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'For small businesses getting started with PAYROW.',
                'employee_limit' => 10,
                'prices' => [
                    'monthly' => 100,
                    'quarterly' => 270,
                    'semi_annual' => 500,
                    'annual' => 1000,
                ],
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'For growing businesses with larger teams.',
                'employee_limit' => 25,
                'prices' => [
                    'monthly' => 200,
                    'quarterly' => 540,
                    'semi_annual' => 1000,
                    'annual' => 2000,
                ],
            ],
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'description' => 'For established businesses managing a larger workforce.',
                'employee_limit' => 50,
                'prices' => [
                    'monthly' => 350,
                    'quarterly' => 945,
                    'semi_annual' => 1750,
                    'annual' => 3500,
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For businesses with larger HR and payroll requirements.',
                'employee_limit' => 100,
                'prices' => [
                    'monthly' => 600,
                    'quarterly' => 1620,
                    'semi_annual' => 3000,
                    'annual' => 6000,
                ],
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For larger organizations with extensive workforce requirements.',
                'employee_limit' => 250,
                'prices' => [
                    'monthly' => 1200,
                    'quarterly' => 3240,
                    'semi_annual' => 6000,
                    'annual' => 12000,
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited employees with custom enterprise pricing.',
                'employee_limit' => null,
                'prices' => [
                    'monthly' => 0,
                    'quarterly' => 0,
                    'semi_annual' => 0,
                    'annual' => 0,
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $prices = $planData['prices'];

            unset($planData['prices']);

            $plan = SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );

            foreach ($prices as $billingCycle => $amount) {
                $plan->prices()->updateOrCreate(
                    ['billing_cycle' => $billingCycle],
                    [
                        'amount' => $amount,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}