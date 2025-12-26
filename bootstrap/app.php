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
    ->withMiddleware(function (Middleware $middleware): void {
        // Принудительное использование HTTPS (глобально)
        $middleware->append(\App\Http\Middleware\ForceHttps::class);
        
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'deploy.token' => \App\Http\Middleware\VerifyDeployToken::class,
            'subscription.check' => \App\Http\Middleware\CheckSubscription::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Не логируем ошибки Method Not Allowed для API эндпоинтов
        // Это нормальное поведение, когда браузер или бот делает GET запрос на POST эндпоинт
        $exceptions->dontReport([
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        ]);
    })->create();
