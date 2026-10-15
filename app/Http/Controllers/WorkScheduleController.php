<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\WorkSchedule;
use App\Services\WorkScheduleService;
use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkScheduleController extends Controller
{
    public function __construct(
        protected CompanyContext $companyContext,
        protected WorkScheduleService $workScheduleService
    ) {}

    public function index(Request $request): Response
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $query = WorkSchedule::query()
            ->where('company_id', $company->id)
            ->withCount('employeeAssignments')
            ->with('days')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->string('search'));

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->string('status') === 'active'
            );
        }

        $schedules = $query
            ->paginate(15)
            ->withQueryString()
            ->through(function (WorkSchedule $schedule) {
                return [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'code' => $schedule->code,
                    'description' => $schedule->description,
                    'default_start_time' => $schedule->default_start_time,
                    'default_end_time' => $schedule->default_end_time,
                    'default_break_minutes' => $schedule->default_break_minutes,
                    'grace_period_minutes' => $schedule->grace_period_minutes,
                    'early_departure_grace_minutes' =>
                        $schedule->early_departure_grace_minutes,
                    'overtime_after_minutes' =>
                        $schedule->overtime_after_minutes,
                    'allow_overtime' => $schedule->allow_overtime,
                    'is_active' => $schedule->is_active,
                    'employee_assignments_count' =>
                        $schedule->employee_assignments_count,
                    'days' => $schedule->days
                        ->sortBy('day_of_week')
                        ->values()
                        ->map(fn ($day) => [
                            'day_of_week' => $day->day_of_week,
                            'is_working_day' => $day->is_working_day,
                            'start_time' => $day->start_time,
                            'end_time' => $day->end_time,
                            'break_minutes' => $day->break_minutes,
                        ]),
                ];
            });

        return Inertia::render('Attendance/WorkSchedules/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'schedules' => $schedules,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        return Inertia::render('Attendance/WorkSchedules/Create', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('work_schedules', 'code')
                    ->where('company_id', $company->id),
            ],
            'description' => ['nullable', 'string'],

            'default_start_time' => ['required', 'date_format:H:i'],
            'default_end_time' => ['required', 'date_format:H:i'],
            'default_break_minutes' => ['required', 'integer', 'min:0'],
            'grace_period_minutes' => ['required', 'integer', 'min:0'],
            'early_departure_grace_minutes' => [
                'required',
                'integer',
                'min:0',
            ],
            'overtime_after_minutes' => [
                'required',
                'integer',
                'min:0',
            ],

            'allow_overtime' => ['boolean'],
            'is_active' => ['boolean'],

            'days' => ['required', 'array', 'size:7'],

            'days.*.day_of_week' => [
                'required',
                'integer',
                'between:1,7',
            ],

            'days.*.is_working_day' => ['boolean'],

            'days.*.start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'days.*.end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'days.*.break_minutes' => [
                'required',
                'integer',
                'min:0',
            ],

            'days.*.grace_period_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'days.*.early_departure_grace_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'days.*.allow_overtime' => ['nullable', 'boolean'],
        ]);

        $dayNumbers = collect($validated['days'])
            ->pluck('day_of_week')
            ->sort()
            ->values()
            ->all();

        abort_unless(
            $dayNumbers === [1, 2, 3, 4, 5, 6, 7],
            422,
            'The weekly schedule must contain Monday through Sunday.'
        );

        $schedule = \DB::transaction(function () use ($company, $validated) {
            $schedule = WorkSchedule::create([
                'company_id' => $company->id,
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'] ?? null,
                'default_start_time' => $validated['default_start_time'],
                'default_end_time' => $validated['default_end_time'],
                'default_break_minutes' =>
                    $validated['default_break_minutes'],
                'grace_period_minutes' =>
                    $validated['grace_period_minutes'],
                'early_departure_grace_minutes' =>
                    $validated['early_departure_grace_minutes'],
                'overtime_after_minutes' =>
                    $validated['overtime_after_minutes'],
                'allow_overtime' =>
                    $validated['allow_overtime'] ?? true,
                'is_active' =>
                    $validated['is_active'] ?? true,
            ]);

            foreach ($validated['days'] as $day) {
                $schedule->days()->create([
                    'day_of_week' => $day['day_of_week'],
                    'is_working_day' =>
                        $day['is_working_day'] ?? true,
                    'start_time' => $day['start_time'] ?? null,
                    'end_time' => $day['end_time'] ?? null,
                    'break_minutes' =>
                        $day['break_minutes'] ?? 0,
                    'grace_period_minutes' =>
                        $day['grace_period_minutes'] ?? null,
                    'early_departure_grace_minutes' =>
                        $day['early_departure_grace_minutes'] ?? null,
                    'allow_overtime' =>
                        $day['allow_overtime'] ?? null,
                ]);
            }

            return $schedule;
        });

        return redirect()
            ->route('work-schedules.show', $schedule)
            ->with('success', 'Work schedule created successfully.');
    }

    public function show(
        Request $request,
        WorkSchedule $workSchedule
    ): Response {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $workSchedule->company_id === (int) $company->id,
            404
        );

        $workSchedule->load([
            'days' => fn ($query) => $query->orderBy('day_of_week'),
        ]);

        $workSchedule->loadCount('employeeAssignments');

        return Inertia::render('Attendance/WorkSchedules/Show', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'schedule' => [
                'id' => $workSchedule->id,
                'name' => $workSchedule->name,
                'code' => $workSchedule->code,
                'description' => $workSchedule->description,
                'default_start_time' =>
                    $workSchedule->default_start_time,
                'default_end_time' =>
                    $workSchedule->default_end_time,
                'default_break_minutes' =>
                    $workSchedule->default_break_minutes,
                'grace_period_minutes' =>
                    $workSchedule->grace_period_minutes,
                'early_departure_grace_minutes' =>
                    $workSchedule->early_departure_grace_minutes,
                'overtime_after_minutes' =>
                    $workSchedule->overtime_after_minutes,
                'allow_overtime' =>
                    $workSchedule->allow_overtime,
                'is_active' =>
                    $workSchedule->is_active,
                'employee_assignments_count' =>
                    $workSchedule->employee_assignments_count,
                'days' => $workSchedule->days->map(fn ($day) => [
                    'id' => $day->id,
                    'day_of_week' => $day->day_of_week,
                    'is_working_day' => $day->is_working_day,
                    'start_time' => $day->start_time,
                    'end_time' => $day->end_time,
                    'break_minutes' => $day->break_minutes,
                    'grace_period_minutes' =>
                        $day->grace_period_minutes,
                    'early_departure_grace_minutes' =>
                        $day->early_departure_grace_minutes,
                    'allow_overtime' =>
                        $day->allow_overtime,
                ]),
            ],
        ]);
    }

    protected function authorizeCompanyAccess(
        Request $request,
        Company $company
    ): void {
        if ($request->user()?->is_super_admin === true) {
            return;
        }

        abort_unless(
            $request->user()
                ?->companies()
                ->where('companies.id', $company->id)
                ->wherePivot('is_active', true)
                ->exists(),
            403,
            'You do not have access to this company.'
        );

        abort_unless(
            $request->user()->can('attendance.manage'),
            403,
            'You do not have permission to manage attendance.'
        );
    }


        public function edit(
        Request $request,
        WorkSchedule $workSchedule
    ): Response {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $workSchedule->company_id === (int) $company->id,
            404
        );

        $workSchedule->load([
            'days' => fn ($query) => $query->orderBy('day_of_week'),
        ]);

        return Inertia::render('Attendance/WorkSchedules/Edit', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],

            'schedule' => [
                'id' => $workSchedule->id,
                'name' => $workSchedule->name,
                'code' => $workSchedule->code,
                'description' => $workSchedule->description,

                'default_start_time' =>
                    $workSchedule->default_start_time,

                'default_end_time' =>
                    $workSchedule->default_end_time,

                'default_break_minutes' =>
                    $workSchedule->default_break_minutes,

                'grace_period_minutes' =>
                    $workSchedule->grace_period_minutes,

                'early_departure_grace_minutes' =>
                    $workSchedule->early_departure_grace_minutes,

                'overtime_after_minutes' =>
                    $workSchedule->overtime_after_minutes,

                'allow_overtime' =>
                    $workSchedule->allow_overtime,

                'is_active' =>
                    $workSchedule->is_active,

                'days' => $workSchedule->days->map(
                    fn ($day) => [
                        'id' => $day->id,
                        'day_of_week' => $day->day_of_week,
                        'is_working_day' => $day->is_working_day,
                        'start_time' => $day->start_time,
                        'end_time' => $day->end_time,
                        'break_minutes' => $day->break_minutes,
                        'grace_period_minutes' =>
                            $day->grace_period_minutes,
                        'early_departure_grace_minutes' =>
                            $day->early_departure_grace_minutes,
                        'allow_overtime' =>
                            $day->allow_overtime,
                    ]
                ),
            ],
        ]);
    }


        public function update(
        Request $request,
        WorkSchedule $workSchedule
    ) {
        $company = $this->companyContext->get();

        abort_unless($company, 400, 'No active company selected.');

        $this->authorizeCompanyAccess($request, $company);

        abort_unless(
            (int) $workSchedule->company_id === (int) $company->id,
            404
        );

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('work_schedules', 'code')
                    ->where('company_id', $company->id)
                    ->ignore($workSchedule->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'default_start_time' => [
                'required',
                'date_format:H:i',
            ],

            'default_end_time' => [
                'required',
                'date_format:H:i',
            ],

            'default_break_minutes' => [
                'required',
                'integer',
                'min:0',
            ],

            'grace_period_minutes' => [
                'required',
                'integer',
                'min:0',
            ],

            'early_departure_grace_minutes' => [
                'required',
                'integer',
                'min:0',
            ],

            'overtime_after_minutes' => [
                'required',
                'integer',
                'min:0',
            ],

            'allow_overtime' => [
                'boolean',
            ],

            'is_active' => [
                'boolean',
            ],

            'days' => [
                'required',
                'array',
                'size:7',
            ],

            'days.*.day_of_week' => [
                'required',
                'integer',
                'between:1,7',
            ],

            'days.*.is_working_day' => [
                'boolean',
            ],

            'days.*.start_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'days.*.end_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'days.*.break_minutes' => [
                'required',
                'integer',
                'min:0',
            ],

            'days.*.grace_period_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'days.*.early_departure_grace_minutes' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'days.*.allow_overtime' => [
                'nullable',
                'boolean',
            ],
        ]);

        $dayNumbers = collect($validated['days'])
            ->pluck('day_of_week')
            ->sort()
            ->values()
            ->all();

        abort_unless(
            $dayNumbers === [1, 2, 3, 4, 5, 6, 7],
            422,
            'The weekly schedule must contain Monday through Sunday.'
        );

        \DB::transaction(function () use (
            $workSchedule,
            $validated
        ) {
            $workSchedule->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'] ?? null,

                'default_start_time' =>
                    $validated['default_start_time'],

                'default_end_time' =>
                    $validated['default_end_time'],

                'default_break_minutes' =>
                    $validated['default_break_minutes'],

                'grace_period_minutes' =>
                    $validated['grace_period_minutes'],

                'early_departure_grace_minutes' =>
                    $validated['early_departure_grace_minutes'],

                'overtime_after_minutes' =>
                    $validated['overtime_after_minutes'],

                'allow_overtime' =>
                    $validated['allow_overtime'] ?? true,

                'is_active' =>
                    $validated['is_active'] ?? true,
            ]);

            foreach ($validated['days'] as $day) {
                $workSchedule->days()->updateOrCreate(
                    [
                        'day_of_week' => $day['day_of_week'],
                    ],
                    [
                        'is_working_day' =>
                            $day['is_working_day'] ?? true,

                        'start_time' =>
                            $day['start_time'] ?? null,

                        'end_time' =>
                            $day['end_time'] ?? null,

                        'break_minutes' =>
                            $day['break_minutes'] ?? 0,

                        'grace_period_minutes' =>
                            $day['grace_period_minutes'] ?? null,

                        'early_departure_grace_minutes' =>
                            $day['early_departure_grace_minutes'] ?? null,

                        'allow_overtime' =>
                            $day['allow_overtime'] ?? null,
                    ]
                );
            }
        });

        return redirect()
            ->route('work-schedules.show', $workSchedule)
            ->with(
                'success',
                'Work schedule updated successfully.'
            );
    }
    
}