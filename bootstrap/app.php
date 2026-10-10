<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetActiveCompany;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'super_admin' => SuperAdminMiddleware::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            SetActiveCompany::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();