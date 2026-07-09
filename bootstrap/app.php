<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
   ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )->withMiddleware(function (Middleware $middleware) {

        /*
        |--------------------------------------------------------------------------
        | Global Middleware ( $middleware )
        |--------------------------------------------------------------------------
        */
        $middleware->append([
            \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Middleware Groups ( $middlewareGroups )
        |--------------------------------------------------------------------------
        */
        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->group('api', [
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':60,1',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Route Middleware Aliases ( $routeMiddleware )
        |--------------------------------------------------------------------------
        */
        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'remove_empty_query' => \App\Http\Middleware\RemoveEmptyQueryParams::class,
            'permission' => \App\Http\Middleware\RouteNeedsPermission::class,
            'redirect_dynamic_login' => \App\Http\Middleware\RedirectDynamicLoginUrl::class,
            'agent_addons' => \App\Http\Middleware\AgentAddonChecking::class,
        ]);
    })
   ->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (\Throwable $e, $request) {
        // if ($e instanceof \App\Exceptions\CustomException) {
        //     return response()->json([
        //         'message' => $e->getMessage(),
        //     ], 400);
        // }
    });
})->create();
