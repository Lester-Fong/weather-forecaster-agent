<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configure CSRF protection
        $middleware->validateCsrfTokens(except: [
            // Exclude API routes from CSRF protection
            'api/weather/query',
            'api/weather/detect-location',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
