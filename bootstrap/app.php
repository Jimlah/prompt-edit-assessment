<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TrimTrailingSlashes;
use App\Http\Middleware\VerifyPixelforgeSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Prism\Prism\Exceptions\PrismException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->use([
            TrimTrailingSlashes::class,
        ]);

        $middleware->alias([
            'pixelforge.signature' => VerifyPixelforgeSignature::class,
        ]);

        // Pixelforge signs its webhook deliveries; the signature middleware
        // is the authenticity check, so CSRF does not apply to that route.
        $middleware->validateCsrfTokens(except: [
            'webhooks/pixelforge',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PrismException $exception, Request $request) {
            return back()->with('error', $exception->getMessage());
        });

        $exceptions->render(function (ConnectionException $exception, Request $request) {
            return back()->with('error', 'PixelForge could not be reached. Please try again.');
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
