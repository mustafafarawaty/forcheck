<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'teacher.auth' => \App\Http\Middleware\EnsureTeacherAuthenticated::class,
            'student.auth' => \App\Http\Middleware\EnsureStudentAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ensure every exception is reported to the log so Railway captures it.
        // By default Laravel may suppress certain exception types; this makes
        // all unhandled exceptions visible in the deployment logs.
        $exceptions->reportable(function (\Throwable $e): bool {
            \Illuminate\Support\Facades\Log::error($e->getMessage(), [
                'exception' => $e,
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return false; // false = let Laravel's default handler also run
        });
    })->create();
