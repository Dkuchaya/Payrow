<?php
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\CompanyMemberController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserCompanyAccessController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SubscriptionPlanController;
use App\Http\Controllers\CompanySubscriptionController;
use App\Http\Controllers\AccountingTransactionController;
use App\Http\Controllers\EmployeeSeparationController;
use App\Http\Controllers\EmployeeSeparationManagementController;
use App\Http\Controllers\EmployeeSeparationClearanceController;
use App\Http\Controllers\EmployeeFinalSettlementController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeavePolicyController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveController;


use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

});



Route::middleware(['auth', 'super_admin'])->group(function () {

    Route::resource('companies', CompanyController::class)
        ->except(['show', 'destroy']);

});


Route::middleware(['auth'])->group(function () {

    Route::get('/settings', [SettingsController::class, 'index'])
        ->name('settings');

    Route::put('/settings/account', [SettingsController::class, 'updateAccount'])
        ->name('settings.account.update');

    Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])
        ->name('settings.preferences.update');

    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])
        ->name('settings.notifications.update');

    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])
        ->name('settings.password.update');

});

// These routes currently authenticate the user but don't yet enforce whether the user is a Super Admin or authorized Company Admin.
// We'll add that before considering this module production-ready.

Route::middleware(['auth'])->group(function () {

    Route::get(
        '/companies/{company}/users',
        [CompanyMemberController::class, 'index']
    )->name('companies.users.index');

    Route::post(
        '/companies/{company}/users',
        [CompanyMemberController::class, 'store']
    )->name('companies.users.store');

    Route::patch(
        '/companies/{company}/users/{user}',
        [CompanyMemberController::class, 'update']
    )->name('companies.users.update');

    Route::patch(
        '/companies/{company}/users/{user}/status',
        [CompanyMemberController::class, 'toggleStatus']
    )->name('companies.users.status');

    Route::delete(
        '/companies/{company}/users/{user}',
        [CompanyMemberController::class, 'destroy']
    )->name('companies.users.destroy');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/settings/company', [
        CompanySettingsController::class,
        'index',
    ])->name('settings.company');

    Route::put('/settings/company', [
        CompanySettingsController::class,
        'update',
    ])->name('settings.company.update');

    Route::post('/settings/company/logo', [
        CompanySettingsController::class,
        'updateLogo',
    ])->name('settings.company.logo');

    Route::delete('/settings/company/logo', [
        CompanySettingsController::class,
        'removeLogo',
    ])->name('settings.company.logo.remove');

});

Route::middleware(['auth'])->group(function () {

    Route::post(
        '/company/switch/{company}',
        CompanySwitchController::class
    )->name('company.switch');

});

Route::get('/users/{user}/company-access', [UserCompanyAccessController::class, 'index'])
    ->name('users.company-access');

Route::post('/users/{user}/company-access', [UserCompanyAccessController::class, 'store'])
    ->name('users.company-access.store');

Route::put('/users/{user}/company-access/{company}', [UserCompanyAccessController::class, 'update'])
    ->name('users.company-access.update');

Route::delete('/users/{user}/company-access/{company}', [UserCompanyAccessController::class, 'destroy'])
    ->name('users.company-access.destroy');


Route::middleware(['auth'])->group(function () {

    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->name('users.store');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->name('users.show');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->name('users.edit');

    Route::put('/users/{user}', [UserController::class, 'update'])
        ->name('users.update');

    Route::post('/users/{user}/toggle-status', [
        UserController::class,
        'toggleStatus',
    ])->name('users.toggle-status');

    Route::delete('/users/{user}/company-access', [
        UserController::class,
        'removeCompanyAccess',
    ])->name('users.remove-company-access');
});


// Department routes

Route::middleware(['auth'])->group(function () {
    Route::get('/departments', [DepartmentController::class, 'index'])
        ->name('departments.index');

    Route::get('/departments/create', [DepartmentController::class, 'create'])
        ->name('departments.create');

    Route::post('/departments', [DepartmentController::class, 'store'])
        ->name('departments.store');

    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
        ->name('departments.edit');

    Route::put('/departments/{department}', [DepartmentController::class, 'update'])
        ->name('departments.update');

    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
        ->name('departments.destroy');
});

// Positions routes

Route::middleware(['auth'])->group(function () {
    Route::get('/positions', [PositionController::class, 'index'])
        ->name('positions.index');

    Route::get('/positions/create', [PositionController::class, 'create'])
        ->name('positions.create');

    Route::post('/positions', [PositionController::class, 'store'])
        ->name('positions.store');

    Route::get('/positions/{position}/edit', [PositionController::class, 'edit'])
        ->name('positions.edit');

    Route::put('/positions/{position}', [PositionController::class, 'update'])
        ->name('positions.update');

    Route::delete('/positions/{position}', [PositionController::class, 'destroy'])
        ->name('positions.destroy');
});

// Employees routes
Route::middleware(['auth'])->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->name('employees.index');

    Route::get('/employees/create', [EmployeeController::class, 'create'])
        ->name('employees.create');

    Route::post('/employees', [EmployeeController::class, 'store'])
        ->name('employees.store');

    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
    ->name('employees.show');

    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])
    ->name('employees.edit');

    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])
        ->name('employees.update');
            Route::get(
        '/employees/{employee}/separation/create',
        [EmployeeSeparationController::class, 'create']
    )->name('employees.separation.create');

    Route::post(
        '/employees/{employee}/separation',
        [EmployeeSeparationController::class, 'store']
    )->name('employees.separation.store');
    

    Route::prefix('employee-separations')
    ->name('employee-separations.')
    ->group(function () {

    //  EmployeeSeparationManagement Rooutes
    Route::get('/', [
            EmployeeSeparationManagementController::class,
            'index'
        ])->name('index');

        Route::get('/{separation}', [
            EmployeeSeparationManagementController::class,
            'show'
        ])->name('show');

        Route::post('/{separation}/approve', [
            EmployeeSeparationManagementController::class,
            'approve'
        ])->name('approve');

        Route::post(
        '/{separation}/clearance',
        [EmployeeSeparationManagementController::class, 'startClearance']
        )->name('clearance');

        Route::post('/{separation}/clearance', [
            EmployeeSeparationManagementController::class,
            'startClearance'
        ])->name('clearance');

        Route::post('/{separation}/final-payroll', [
            EmployeeSeparationManagementController::class,
            'sendToFinalPayroll'
        ])->name('final-payroll');

        Route::post('/{separation}/complete', [
            EmployeeSeparationManagementController::class,
            'complete'
        ])->name('complete');

        Route::post('/{separation}/cancel', [
            EmployeeSeparationManagementController::class,
            'cancel'
        ])->name('cancel');
    });

    Route::prefix('employee-separations/{separation}/clearance')
    ->name('employee-separations.clearance.')
    ->group(function () {
        Route::get(
            '/',
            [EmployeeSeparationClearanceController::class, 'index']
        )->name('index');
    });

    Route::prefix('employee-separation-clearances')
    ->name('employee-separation-clearances.')
    ->group(function () {
        Route::post(
            '/{clearance}/clear',
            [EmployeeSeparationClearanceController::class, 'clear']
        )->name('clear');

        Route::post(
            '/{clearance}/reject',
            [EmployeeSeparationClearanceController::class, 'reject']
        )->name('reject');

        Route::put(
            '/{clearance}',
            [EmployeeSeparationClearanceController::class, 'update']
        )->name('update');
    });

    Route::prefix('employee-separations/{separation}/final-settlement')
    ->name('employee-separations.final-settlement.')
    ->group(function () {

        Route::get(
            '/',
            [EmployeeFinalSettlementController::class, 'show']
        )->name('show');
    });

Route::prefix('employee-final-settlements')
    ->name('employee-final-settlements.')
    ->group(function () {

        Route::put(
            '/{settlement}',
            [EmployeeFinalSettlementController::class, 'update']
        )->name('update');

        Route::post(
            '/{settlement}/submit',
            [EmployeeFinalSettlementController::class, 'submitForReview']
        )->name('submit');

        Route::post(
            '/{settlement}/approve',
            [EmployeeFinalSettlementController::class, 'approve']
        )->name('approve');

        Route::post(
            '/{settlement}/paid',
            [EmployeeFinalSettlementController::class, 'markPaid']
        )->name('paid');

        Route::post(
            '/{settlement}/cancel',
            [EmployeeFinalSettlementController::class, 'cancel']
        )->name('cancel');
    });

});

// Subscription routes
Route::middleware(['auth', 'super_admin'])->group(function () {
    Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])
        ->name('subscription-plans.index');

    Route::get('/subscription-plans/create', [SubscriptionPlanController::class, 'create'])
        ->name('subscription-plans.create');

    Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store'])
        ->name('subscription-plans.store');

    Route::post(
        '/company-subscriptions/{companySubscription}/renew',
        [CompanySubscriptionController::class, 'renew']
    )->name('company-subscriptions.renew');

    Route::get('/subscription-plans/{subscriptionPlan}/edit', [SubscriptionPlanController::class, 'edit'])
        ->name('subscription-plans.edit');

    Route::put('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'update'])
        ->name('subscription-plans.update');

    Route::delete('/subscription-plans/{subscriptionPlan}', [SubscriptionPlanController::class, 'destroy'])
        ->name('subscription-plans.destroy');

    Route::post(
    '/company-subscriptions/{companySubscription}/suspend',
            [CompanySubscriptionController::class, 'suspend']
        )->name('company-subscriptions.suspend');

    Route::post(
    '/company-subscriptions/{companySubscription}/cancel',
        [CompanySubscriptionController::class, 'cancel']
    )->name('company-subscriptions.cancel');

    Route::post(
    '/company-subscriptions/{companySubscription}/record-payment',
        [CompanySubscriptionController::class, 'recordPayment']
    )->name('company-subscriptions.record-payment');


Route::middleware(['auth'])->group(function () {
    Route::get(
        '/accounting/history',
        [AccountingTransactionController::class, 'index']
    )->name('accounting.history');
});
        
});

// Company Subscription routes
Route::middleware(['auth', 'super_admin'])->group(function () {

    Route::get(
        '/company-subscriptions',
        [CompanySubscriptionController::class, 'index']
    )->name('company-subscriptions.index');

    Route::get(
        '/company-subscriptions/create',
        [CompanySubscriptionController::class, 'create']
    )->name('company-subscriptions.create');

    Route::post(
        '/company-subscriptions',
        [CompanySubscriptionController::class, 'store']
    )->name('company-subscriptions.store');

    Route::get(
        '/company-subscriptions/{companySubscription}',
        [CompanySubscriptionController::class, 'show']
    )->name('company-subscriptions.show');

    Route::get(
        '/company-subscriptions/{companySubscription}/edit',
        [CompanySubscriptionController::class, 'edit']
    )->name('company-subscriptions.edit');

    Route::put(
        '/company-subscriptions/{companySubscription}',
        [CompanySubscriptionController::class, 'update']
    )->name('company-subscriptions.update');

    Route::delete(
        '/company-subscriptions/{companySubscription}',
        [CompanySubscriptionController::class, 'destroy']
    )->name('company-subscriptions.destroy');
});

// Company Leave routes
Route::middleware(['auth'])->group(function () {
Route::prefix('leave/types')
    ->name('leave.types.')
    ->group(function () {

        Route::get('/', [
            LeaveTypeController::class,
            'index'
        ])->name('index');

        Route::post('/', [
            LeaveTypeController::class,
            'store'
        ])->name('store');

        Route::put('/{leaveType}', [
            LeaveTypeController::class,
            'update'
        ])->name('update');

        Route::patch('/{leaveType}/toggle-status', [
            LeaveTypeController::class,
            'toggleStatus'
        ])->name('toggle-status');

        Route::delete('/{leaveType}', [
            LeaveTypeController::class,
            'destroy'
        ])->name('destroy');
    });

});
   
    Route::prefix('leave/policies')
    ->name('leave.policies.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [
            LeavePolicyController::class,
            'index',
        ])->name('index');

        Route::post('/', [
            LeavePolicyController::class,
            'store',
        ])->name('store');

        Route::put('/{policy}', [
            LeavePolicyController::class,
            'update',
        ])->name('update');

        Route::patch('/{policy}/toggle-status', [
            LeavePolicyController::class,
            'toggleStatus',
        ])->name('toggle-status');

        Route::delete('/{policy}', [
            LeavePolicyController::class,
            'destroy',
        ])->name('destroy');
    });

    Route::prefix('leave/balances')
    ->name('leave.balances.')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('/', [
            LeaveBalanceController::class,
            'index',
        ])->name('index');

        Route::post('/generate', [
            LeaveBalanceController::class,
            'generate',
        ])->name('generate');

        Route::patch('/{balance}/adjust', [
            LeaveBalanceController::class,
            'adjust',
        ])->name('adjust');

        Route::post('/carry-forward', [LeaveBalanceController::class, 'carryForward'])
            ->name('carry-forward');

            Route::post('/accrue', [LeaveBalanceController::class, 'accrue'])
        ->name('accrue');
    });

   Route::prefix('leave/requests')
    ->name('leave.requests.')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('/', [
            LeaveRequestController::class,
            'index'
        ])->name('index');

        Route::get('/create', [
            LeaveRequestController::class,
            'create'
        ])->name('create');

        Route::get('/lookup-options', [
            LeaveRequestController::class,
            'options'
        ])->name('lookup-options');

        Route::post('/', [
            LeaveRequestController::class,
            'store'
        ])->name('store');

        Route::get('/{leaveRequest}', [
            LeaveRequestController::class,
            'show'
        ])
            ->whereNumber('leaveRequest')
            ->name('show');

        Route::post('/{leaveRequest}/approve', [
            LeaveRequestController::class,
            'approve'
        ])->name('approve');

        Route::post('/{leaveRequest}/reject', [
            LeaveRequestController::class,
            'reject'
        ])->name('reject');

        Route::post('/{leaveRequest}/cancel', [
            LeaveRequestController::class,
            'cancel'
        ])->name('cancel');

        Route::get('/{leaveRequest}/attachment', [
            LeaveRequestController::class,
            'attachment'
        ])->name('attachment');
    });

    Route::get('/leave', [
        LeaveController::class,
        'index'
    ])
        ->middleware(['auth'])
        ->name('leave.index');
    
  
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
