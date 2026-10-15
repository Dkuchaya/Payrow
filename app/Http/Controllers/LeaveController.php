<?php

namespace App\Http\Controllers;

use App\Support\CompanyContext;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $company = app(CompanyContext::class)->get();

        abort_unless(
            $company,
            403,
            'No active company selected.'
        );

        return Inertia::render('Leave/Index', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
        ]);
    }
}