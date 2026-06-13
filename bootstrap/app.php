<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        $middleware->alias([
            'auth.jwt' => \App\Http\Middleware\Authenticate::class,
            'tenant'   => \App\Http\Middleware\ResolveTenant::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenExpiredException $e, Request $request) {
            return response()->json(['message' => 'Token expirado'], 401);
        });

        $exceptions->render(function (TokenInvalidException $e, Request $request) {
            return response()->json(['message' => 'Token inválido'], 401);
        });

        $exceptions->render(function (JWTException $e, Request $request) {
            return response()->json(['message' => 'Token ausente'], 401);
        });
    })
    ->create();
