<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $plans = SubscriptionPlan::query()
            ->withCount('subscriptions')
            ->with([
                'prices' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderByRaw("
                        FIELD(
                            billing_cycle,
                            'monthly',
                            'quarterly',
                            'semi_annual',
                            'annual'
                        )
                    "),
            ])
            ->orderBy('employee_limit')
            ->orderBy('name')
            ->get();

        return Inertia::render('Subscriptions/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        return Inertia::render('Subscriptions/Plans/Create');
    }

    public function store(Request $request)
    {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                'unique:subscription_plans,slug',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'employee_limit' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_active' => [
                'boolean',
            ],

            'prices' => [
                'required',
                'array',
            ],

            'prices.monthly' => [
                'required',
                'numeric',
                'min:0',
            ],

            'prices.quarterly' => [
                'required',
                'numeric',
                'min:0',
            ],

            'prices.semi_annual' => [
                'required',
                'numeric',
                'min:0',
            ],

            'prices.annual' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $plan = SubscriptionPlan::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'employee_limit' => $validated['employee_limit'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['prices'] as $billingCycle => $amount) {
            $plan->prices()->create([
                'billing_cycle' => $billingCycle,
                'amount' => $amount,
                'is_active' => true,
            ]);
        }

        return redirect()
            ->route('subscription-plans.index')
            ->with('success', 'Subscription plan created successfully.');
    }

    public function edit(
        Request $request,
        SubscriptionPlan $subscriptionPlan
    ): Response {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $subscriptionPlan->load([
            'prices' => fn ($query) => $query
                ->where('is_active', true),
        ]);

        return Inertia::render('Subscriptions/Plans/Edit', [
            'plan' => $subscriptionPlan,
        ]);
    }

    public function update(
        Request $request,
        SubscriptionPlan $subscriptionPlan
    ) {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'slug' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('subscription_plans', 'slug')
                    ->ignore($subscriptionPlan->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'employee_limit' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_active' => [
                'boolean',
            ],

            'prices' => [
                'required',
                'array',
            ],

            'prices.monthly' => [
                'required',
                'numeric',
                'min:0',
            ],

            'prices.quarterly' => [
                'required',
                'numeric',
                'min:0',
            ],

            'prices.semi_annual' => [
                'required',
                'numeric',
                'min:0',
            ],

            'prices.annual' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $subscriptionPlan->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'employee_limit' => $validated['employee_limit'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
        ]);

        foreach ($validated['prices'] as $billingCycle => $amount) {
            $subscriptionPlan->prices()->updateOrCreate(
                [
                    'billing_cycle' => $billingCycle,
                ],
                [
                    'amount' => $amount,
                    'is_active' => true,
                ]
            );
        }

        return redirect()
            ->route('subscription-plans.index')
            ->with('success', 'Subscription plan updated successfully.');
    }

    public function destroy(
        Request $request,
        SubscriptionPlan $subscriptionPlan
    ) {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        if ($subscriptionPlan->subscriptions()->exists()) {
            return back()->with(
                'error',
                'This subscription plan cannot be deleted because it has company subscriptions. Deactivate it instead.'
            );
        }

        $subscriptionPlan->delete();

        return back()->with(
            'success',
            'Subscription plan deleted successfully.'
        );
    }
}