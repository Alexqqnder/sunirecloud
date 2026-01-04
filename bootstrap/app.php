<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Registrar middleware aliases
        $middleware->alias([
            'company.active' => \App\Http\Middleware\EnsureCompanyIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Mapeo de modelos a nombres amigables en español
        $modelNames = [
            'App\\Models\\Company' => 'empresa',
            'App\\Models\\Branch' => 'sucursal',
            'App\\Models\\Client' => 'cliente',
            'App\\Models\\Invoice' => 'factura',
            'App\\Models\\Boleta' => 'boleta',
            'App\\Models\\CreditNote' => 'nota de crédito',
            'App\\Models\\DebitNote' => 'nota de débito',
            'App\\Models\\DispatchGuide' => 'guía de remisión',
            'App\\Models\\Retention' => 'retención',
            'App\\Models\\Correlative' => 'correlativo',
            'App\\Models\\User' => 'usuario',
            'App\\Models\\DailySummary' => 'resumen diario',
            'App\\Models\\VoidedDocument' => 'comunicación de baja',
        ];

        // Manejar ModelNotFoundException (modelo no encontrado)
        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($modelNames) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $modelClass = $e->getModel();
                $modelName = $modelNames[$modelClass] ?? 'recurso';
                $ids = $e->getIds();
                $id = !empty($ids) ? $ids[0] : 'desconocido';

                return response()->json([
                    'success' => false,
                    'message' => "No se encontró la {$modelName} con ID: {$id}",
                    'error' => [
                        'type' => 'not_found',
                        'model' => $modelName,
                        'id' => $id
                    ]
                ], 404);
            }
        });

        // Manejar NotFoundHttpException (ruta no encontrada)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($modelNames) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Verificar si es un ModelNotFoundException envuelto
                $previous = $e->getPrevious();
                if ($previous instanceof ModelNotFoundException) {
                    $modelClass = $previous->getModel();
                    $modelName = $modelNames[$modelClass] ?? 'recurso';
                    $ids = $previous->getIds();
                    $id = !empty($ids) ? $ids[0] : 'desconocido';

                    return response()->json([
                        'success' => false,
                        'message' => "No se encontró la {$modelName} con ID: {$id}",
                        'error' => [
                            'type' => 'not_found',
                            'model' => $modelName,
                            'id' => $id
                        ]
                    ], 404);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'La ruta solicitada no existe',
                    'error' => [
                        'type' => 'route_not_found',
                        'path' => $request->path()
                    ]
                ], 404);
            }
        });

        // Manejar MethodNotAllowedHttpException (método HTTP no permitido)
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "El método {$request->method()} no está permitido para esta ruta",
                    'error' => [
                        'type' => 'method_not_allowed',
                        'method' => $request->method(),
                        'path' => $request->path(),
                        'allowed_methods' => $e->getHeaders()['Allow'] ?? null
                    ]
                ], 405);
            }
        });

        // Manejar ValidationException (errores de validación)
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $errors = $e->errors();
                $firstError = collect($errors)->flatten()->first();

                return response()->json([
                    'success' => false,
                    'message' => $firstError ?? 'Error de validación',
                    'errors' => $errors
                ], 422);
            }
        });

        // Manejar AuthenticationException (no autenticado)
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autenticado. Por favor inicie sesión.',
                    'error' => [
                        'type' => 'unauthenticated'
                    ]
                ], 401);
            }
        });
    })->create();
