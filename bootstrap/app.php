<?php

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
        $middleware->alias([
            'role.hierarchy' => \App\Http\Middleware\EnsureRoleHierarchy::class,
            'quiz.reset.on.navigation' => \App\Http\Middleware\ResetQuizStateOnNavigation::class,
            'quiz.prevent.random.navigation' => \App\Http\Middleware\PreventNavigationDuringRandomQuiz::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
