<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSeparation;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;

class EmployeeSeparationController extends Controller
{
    public function create(Employee $employee)
    {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $employee->company_id === $company->id,
            403,
            'You do not have access to this employee.'
        );

        abort_if(
            $employee->status === 'separated',
            422,
            'This employee has already been separated.'
        );

        return Inertia::render('Employees/Separation/Create', [
            'employee' => $employee->load([
                'department',
                'position',
            ]),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $company = app(CompanyContext::class)->get();

        abort_unless($company, 403, 'No active company selected.');

        abort_unless(
            $employee->company_id === $company->id,
            403,
            'You do not have access to this employee.'
        );

        abort_if(
            $employee->status === 'separated',
            422,
            'This employee has already been separated.'
        );

        $validated = $request->validate([
            'separation_type' => [
                'required',
                Rule::in([
                    'resignation',
                    'termination',
                    'redundancy',
                    'retirement',
                    'end_of_contract',
                    'dismissal',
                    'death',
                    'abscondment',
                    'mutual_separation',
                    'other',
                ]),
            ],

            'notice_date' => [
                'nullable',
                'date',
            ],

            'effective_date' => [
                'required',
                'date',
            ],

            'last_working_date' => [
                'nullable',
                'date',
                'before_or_equal:effective_date',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        EmployeeSeparation::create([
            ...$validated,
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'status' => 'pending',
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Separation request submitted successfully.');
    }
}