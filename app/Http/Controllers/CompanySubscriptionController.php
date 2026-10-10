<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\SubscriptionPayment;
use App\Models\AccountingTransaction;
use App\Notifications\SubscriptionNotification;

class CompanySubscriptionController extends Controller
{
    /**
     * List company subscriptions.
     */
    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $subscriptions = CompanySubscription::query()
            ->with([
                'company:id,name,slug',
                'plan:id,name,slug,employee_limit',
            ])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Subscriptions/Companies/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    /**
     * Show assign subscription form.
     */
    public function create(Request $request): Response
    {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $companies = Company::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->with([
                'prices' => fn ($query) => $query
                    ->where('is_active', true),
            ])
            ->orderBy('employee_limit')
            ->orderBy('name')
            ->get();

        return Inertia::render('Subscriptions/Companies/Create', [
            'companies' => $companies,
            'plans' => $plans,
        ]);
    }

    /**
     * Assign subscription to company.
     */
    public function store(Request $request)
    {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],

            'subscription_plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id',
            ],

            'billing_cycle' => [
                'required',
                'in:monthly,quarterly,semi_annual,annual',
            ],

            'status' => [
                'required',
                'in:trial,active,past_due,suspended,cancelled,expired',
            ],

            'starts_at' => [
                'required',
                'date',
            ],

            'ends_at' => [
                'nullable',
                'date',
                'after:starts_at',
            ],

            'trial_ends_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],

            'payment_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'paid_at' => [
                'nullable',
                'date',
            ],
        ]);

        $company = Company::findOrFail(
            $validated['company_id']
        );

        $plan = SubscriptionPlan::findOrFail(
            $validated['subscription_plan_id']
        );

        abort_unless(
            $plan->is_active,
            422,
            'The selected subscription plan is inactive.'
        );

        $price = $plan->priceFor(
            $validated['billing_cycle']
        );

        abort_unless(
            $price,
            422,
            'No price has been configured for the selected billing cycle.'
        );

       $subscription = DB::transaction(function () use (
    $validated,
    $company,
    $plan,
    $price
) {
    /**
     * A company should normally have only one active/trial
     * subscription at a time.
     */
    if (
        in_array(
            $validated['status'],
            ['trial', 'active'],
            true
        )
    ) {
        CompanySubscription::query()
            ->where('company_id', $company->id)
            ->whereIn('status', ['trial', 'active'])
            ->update([
                'status' => 'expired',
            ]);
    }

    return CompanySubscription::create([
        'company_id' => $company->id,
        'subscription_plan_id' => $plan->id,
        'status' => $validated['status'],
        'billing_cycle' => $validated['billing_cycle'],
        'amount' => $price->amount,
        'starts_at' => $validated['starts_at'],
        'ends_at' => $validated['ends_at'] ?? null,
        'trial_ends_at' => $validated['trial_ends_at'] ?? null,
        'payment_reference' =>
            $validated['payment_reference'] ?? null,
        'paid_at' =>
            $validated['paid_at'] ?? null,
    ]);
});

/*
|--------------------------------------------------------------------------
| Send notification AFTER successful transaction
|--------------------------------------------------------------------------
*/

$subscription->load([
    'company',
    'plan',
]);

$admins = $company->users()
    ->wherePivot('is_active', true)
    ->get();

foreach ($admins as $admin) {
    $admin->notify(
        new SubscriptionNotification(
            $subscription,
            'assigned'
        )
    );
}

        return redirect()
            ->route('company-subscriptions.index')
            ->with(
                'success',
                "Subscription assigned to {$company->name} successfully."
            );
    }

   public function recordPayment(
    Request $request,
    CompanySubscription $companySubscription
) {
    abort_unless(
        $request->user()?->is_super_admin === true,
        403,
        'Super Admin access required.'
    );

    $validated = $request->validate([
        'amount' => ['required', 'numeric', 'min:0.01'],
        'payment_date' => ['required', 'date'],
        'payment_reference' => ['nullable', 'string', 'max:255'],
        'payment_method' => ['nullable', 'string', 'max:100'],
        'notes' => ['nullable', 'string', 'max:5000'],
    ]);

    DB::transaction(function () use (
        $request,
        $companySubscription,
        $validated
    ) {
        // 1. Record the payment
        $payment = SubscriptionPayment::create([
            'company_subscription_id' => $companySubscription->id,
            'amount' => $validated['amount'],
            'currency' => $companySubscription->company->currency ?? 'ZMW',
            'payment_date' => $validated['payment_date'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        // 2. Update latest payment information
        $companySubscription->update([
            'payment_reference' => $validated['payment_reference'] ?? null,
            'paid_at' => $validated['payment_date'],
        ]);

        // 3. Reactivate a past-due subscription
        if ($companySubscription->status === 'past_due') {
            $companySubscription->update([
                'status' => 'active',
            ]);
        }

        // 4. Create accounting history
        AccountingTransaction::create([
            'company_id' => $companySubscription->company_id,
            'transaction_date' => $validated['payment_date'],
            'transaction_type' => 'payment',
            'reference' => $validated['payment_reference'] ?? null,
            'description' => 'Subscription payment',
            'amount' => $validated['amount'],
            'currency' => $companySubscription->company->currency ?? 'ZMW',
            'source_type' => SubscriptionPayment::class,
            'source_id' => $payment->id,
            'recorded_by' => $request->user()->id,
        ]);
    });

    return redirect()
        ->route('company-subscriptions.show', $companySubscription)
        ->with('success', 'Payment recorded successfully.');
}
    /**
     * Show subscription.
     */
    public function show(
        Request $request,
        CompanySubscription $companySubscription
    ): Response {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $companySubscription->load([
            'company:id,name,slug,logo,currency',
            'plan.prices',
        ]);

        return Inertia::render(
            'Subscriptions/Companies/Show',
            [
                'subscription' => $companySubscription,
            ]
        );
    }

    /**
     * Edit subscription.
     */
    public function edit(
        Request $request,
        CompanySubscription $companySubscription
    ): Response {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $companySubscription->load([
            'company:id,name',
            'plan.prices',
        ]);

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->with([
                'prices' => fn ($query) => $query
                    ->where('is_active', true),
            ])
            ->orderBy('employee_limit')
            ->orderBy('name')
            ->get();

        return Inertia::render(
            'Subscriptions/Companies/Edit',
            [
                'subscription' => $companySubscription,
                'plans' => $plans,
            ]
        );
    }

    /**
     * Update subscription.
     */
    public function update(
        Request $request,
        CompanySubscription $companySubscription
    ) {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $validated = $request->validate([
            'subscription_plan_id' => [
                'required',
                'integer',
                'exists:subscription_plans,id',
            ],

            'billing_cycle' => [
                'required',
                'in:monthly,quarterly,semi_annual,annual',
            ],

            'status' => [
                'required',
                'in:trial,active,past_due,suspended,cancelled,expired',
            ],

            'starts_at' => [
                'required',
                'date',
            ],

            'ends_at' => [
                'nullable',
                'date',
                'after:starts_at',
            ],

            'trial_ends_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],

            'payment_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'paid_at' => [
                'nullable',
                'date',
            ],
        ]);

        $plan = SubscriptionPlan::findOrFail(
            $validated['subscription_plan_id']
        );

        abort_unless(
            $plan->is_active,
            422,
            'The selected subscription plan is inactive.'
        );

        $price = $plan->priceFor(
            $validated['billing_cycle']
        );

        abort_unless(
            $price,
            422,
            'No price has been configured for the selected billing cycle.'
        );

        DB::transaction(function () use (
            $validated,
            $companySubscription,
            $price
        ) {
            if (
                in_array(
                    $validated['status'],
                    ['trial', 'active'],
                    true
                )
            ) {
                CompanySubscription::query()
                    ->where('company_id', $companySubscription->company_id)
                    ->where('id', '!=', $companySubscription->id)
                    ->whereIn('status', ['trial', 'active'])
                    ->update([
                        'status' => 'expired',
                    ]);
            }

            $companySubscription->update([
                'subscription_plan_id' =>
                    $validated['subscription_plan_id'],

                'status' =>
                    $validated['status'],

                'billing_cycle' =>
                    $validated['billing_cycle'],

                'amount' =>
                    $price->amount,

                'starts_at' =>
                    $validated['starts_at'],

                'ends_at' =>
                    $validated['ends_at'] ?? null,

                'trial_ends_at' =>
                    $validated['trial_ends_at'] ?? null,

                'payment_reference' =>
                    $validated['payment_reference'] ?? null,

                'paid_at' =>
                    $validated['paid_at'] ?? null,
            ]);
        });

        return redirect()
            ->route(
                'company-subscriptions.show',
                $companySubscription
            )
            ->with(
                'success',
                'Subscription updated successfully.'
            );
    }


    /**
 * Renew subscription.
 */
public function renew(
    Request $request,
    CompanySubscription $companySubscription
) {
    abort_unless(
        $request->user()?->is_super_admin === true,
        403,
        'Super Admin access required.'
    );

    $companySubscription->load([
        'company',
        'plan.prices',
    ]);

    abort_unless(
        $companySubscription->plan->is_active,
        422,
        'The subscription plan is inactive.'
    );

    $price = $companySubscription->plan->priceFor(
        $companySubscription->billing_cycle
    );

    abort_unless(
        $price,
        422,
        'No price has been configured for this billing cycle.'
    );

    DB::transaction(function () use (
        $companySubscription,
        $price
    ) {

        $now = now();

        /*
         * If the current subscription has a future expiry date,
         * extend from that date.
         *
         * If it has already expired or has no expiry date,
         * start the renewed period from today.
         */
        $renewalStart = $companySubscription->ends_at &&
            $companySubscription->ends_at->isFuture()
            ? $companySubscription->ends_at->copy()
            : $now->copy();

        $newEndDate = $renewalStart->copy()
            ->addMonths($price->durationInMonths());

        /*
         * Expire any other active/trial subscription
         * belonging to the same company.
         */
        CompanySubscription::query()
            ->where('company_id', $companySubscription->company_id)
            ->where('id', '!=', $companySubscription->id)
            ->whereIn('status', ['trial', 'active'])
            ->update([
                'status' => 'expired',
            ]);

        $companySubscription->update([
            'status' => 'active',
            'amount' => $price->amount,
            'starts_at' => $renewalStart,
            'ends_at' => $newEndDate,
            'trial_ends_at' => null,
        ]);
    });

    $subscription = $companySubscription->load(['company', 'plan']);

    $admins = $subscription->company
        ->users()
        ->wherePivot('is_active', true)
        ->get();

    foreach ($admins as $admin) {
        $admin->notify(
            new SubscriptionNotification(
                $subscription,
                'renewed'
            )
        );
    }

    return redirect()
        ->route(
            'company-subscriptions.show',
            $companySubscription
        )
        ->with(
            'success',
            'Subscription renewed successfully.'
        );
}


/**
 * Suspend subscription.
 */
public function suspend(
    Request $request,
    CompanySubscription $companySubscription
) {
    abort_unless(
        $request->user()?->is_super_admin === true,
        403,
        'Super Admin access required.'
    );

    abort_unless(
        in_array(
            $companySubscription->status,
            ['trial', 'active', 'past_due'],
            true
        ),
        422,
        'This subscription cannot be suspended.'
    );

    $companySubscription->update([
        'status' => 'suspended',
    ]);

    $subscription = $companySubscription->load(['company', 'plan']);

$admins = $subscription->company
    ->users()
    ->wherePivot('is_active', true)
    ->get();

    foreach ($admins as $admin) {
        $admin->notify(
            new SubscriptionNotification(
                $subscription,
                'suspended'
            )
        );
    }

    return redirect()
        ->route(
            'company-subscriptions.show',
            $companySubscription
        )
        ->with(
            'success',
            'Subscription suspended successfully.'
        );
}


/**
 * Cancel subscription.
 */
public function cancel(
    Request $request,
    CompanySubscription $companySubscription
) {
    abort_unless(
        $request->user()?->is_super_admin === true,
        403,
        'Super Admin access required.'
    );

    abort_unless(
        in_array(
            $companySubscription->status,
            ['trial', 'active', 'past_due', 'suspended'],
            true
        ),
        422,
        'This subscription cannot be cancelled.'
    );

    $companySubscription->update([
        'status' => 'cancelled',
    ]);

    $subscription = $companySubscription->load(['company', 'plan']);

$admins = $subscription->company
    ->users()
    ->wherePivot('is_active', true)
    ->get();

foreach ($admins as $admin) {
    $admin->notify(
        new SubscriptionNotification(
            $subscription,
            'cancelled'
        )
    );
}

    return redirect()
        ->route(
            'company-subscriptions.show',
            $companySubscription
        )
        ->with(
            'success',
            'Subscription cancelled successfully.'
        );
}

    /**
     * Delete subscription.
     */
    public function destroy(
        Request $request,
        CompanySubscription $companySubscription
    ) {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $companySubscription->delete();

        return back()->with(
            'success',
            'Subscription record deleted successfully.'
        );
    }
}