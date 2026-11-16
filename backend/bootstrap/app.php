<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $status = 500;
            $message = 'Terjadi kesalahan pada layanan.';
            $details = null;

            if ($exception instanceof ValidationException) {
                $status = 422;
                $message = 'Data yang dikirim belum memenuhi aturan validasi.';
                $details = $exception->errors();
            } elseif ($exception instanceof AuthenticationException) {
                $status = 401;
                $message = 'Sesi pengguna tidak tersedia atau sudah berakhir.';
            } elseif ($exception instanceof AuthorizationException) {
                $status = 403;
                $message = $exception->getMessage() ?: 'Tindakan tidak diizinkan.';
            } elseif ($exception instanceof ModelNotFoundException) {
                $status = 404;
                $message = 'Data yang diminta tidak ditemukan.';
            } elseif ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                $message = $exception->getMessage() ?: match ($status) {
                    403 => 'Tindakan tidak diizinkan.',
                    404 => 'Data yang diminta tidak ditemukan.',
                    409 => 'Status data tidak memungkinkan tindakan ini.',
                    419 => 'Sesi berubah. Muat ulang token CSRF.',
                    default => 'Permintaan belum dapat diproses.',
                };
            }

            $error = [
                'code' => $status === 422 ? 'VALIDATION_FAILED' : 'HTTP_'.$status,
                'message' => $message,
            ];

            if ($details !== null) {
                $error['details'] = $details;
            }

            return response()->json(['error' => $error], $status);
        });
    })->create();
