<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway (and most PaaS platforms) terminate TLS at their edge and
        // forward to the container over plain HTTP — without trusting the
        // proxy's X-Forwarded-Proto header, Laravel thinks every request is
        // HTTP and generates http:// asset/URL links even on an https://
        // page, causing mixed-content warnings. The proxy IP isn't fixed or
        // knowable in advance on these platforms, so trust any upstream.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            SetTenantContext::class,
            HandleInertiaRequests::class,
        ]);
        $middleware->api(append: [
            SetTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
