<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\App\Http\Middleware\ForceConfiguredHttps::class);

        $middleware->alias([
            'admin-only' => \App\Http\Middleware\AdminOnly::class,
            'user-only' => \App\Http\Middleware\UserOnly::class,
            'redirect-if-authenticated' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/arrange-teams-meeting',
            '/webhooks/calendly',
            '/webhooks/n8n/receive',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
