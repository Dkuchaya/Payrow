<?php

namespace App\Http\Controllers;

use App\Models\AccountingTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountingTransactionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->is_super_admin === true,
            403,
            'Super Admin access required.'
        );

        $query = AccountingTransaction::query()
            ->with([
                'company:id,name',
                'recordedBy:id,name,email',
            ])
            ->latest('transaction_date')
            ->latest('id');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        if ($request->filled('transaction_type')) {
            $query->where(
                'transaction_type',
                $request->string('transaction_type')
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date('date_to'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');

            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $transactions = $query
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Accounting/History', [
            'transactions' => $transactions,
            'filters' => [
                'company_id' => $request->input('company_id'),
                'transaction_type' => $request->input('transaction_type'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'search' => $request->input('search'),
            ],
        ]);
    }
}