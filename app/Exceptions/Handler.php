<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function shouldReturnJson($request, Throwable $e)
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->shouldReturnJson($request, $exception)
            ? response()->json([
                'success' => false,
                'message' => $exception->getMessage() ?: 'Unauthenticated.',
                'data' => (object) [],
                'errors' => (object) [],
            ], 401)
            : redirect()->guest($exception->redirectTo() ?? route('admin.login'));
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'data' => (object) [],
            'errors' => $exception->errors(),
        ], $exception->status);
    }

    protected function prepareJsonResponse($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;
            $message = $this->isHttpException($e) ? $e->getMessage() : 'Server Error';
            if (! config('app.debug') && $status === 500) {
                $message = 'Something went wrong.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => (object) [],
                'errors' => (object) [],
            ], $status);
        }

        return parent::prepareJsonResponse($request, $e);
    }
}
