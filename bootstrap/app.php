<?php

use App\Exceptions\SlugMovedException;
use App\Http\Middleware\NoStorePrivate;
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
        $middleware->alias([
            'no-store' => NoStorePrivate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Устаревший слаг публичной страницы: постоянный редирект на актуальный URL.
        $exceptions->render(function (SlugMovedException $e, Request $request) {
            if (! $request->isMethodSafe()) {
                return null;
            }

            return redirect()->to($e->redirectUrl, 301);
        });
    })->create();
