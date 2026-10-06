<?php

use App\Http\Middleware\RoleMiddleware;
use App\Support\PortalRedirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Short name so routes can say ->middleware('role:admin,staff')
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Logged-in users who open /login or /register get sent to /home,
        // which then forwards them to the right place for their role.
        $middleware->redirectUsersTo(fn () => route('home'));
        $middleware->redirectGuestsTo(fn (Request $request) => PortalRedirect::loginFor($request));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
