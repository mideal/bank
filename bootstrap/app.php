<?php

use App\Exceptions\AccountNotFoundException;
use App\Exceptions\EmailAlreadyTakenException;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\UserNotFoundException;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorApi;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e) {
            return response()->json((new ApiResponse)->addError(new ErrorApi(401, $e->getMessage())), 401);
        });

        $exceptions->render(function (AuthorizationException $e) {
            return response()->json((new ApiResponse)->addError(new ErrorApi(403, $e->getMessage())), 403);
        });

        $exceptions->render(function (UserNotFoundException $e) {
            return response()->json((new ApiResponse)->addError(new ErrorApi(404, $e->getMessage())), 404);
        });

        $exceptions->render(function (EmailAlreadyTakenException $e) {
            return response()->json((new ApiResponse)->addError(new ErrorApi(422, $e->getMessage(), source: 'email')), 422);
        });

        $exceptions->render(function (AccountNotFoundException $e) {
            return response()->json((new ApiResponse)->addError(new ErrorApi(404, $e->getMessage())), 404);
        });

        $exceptions->render(function (InsufficientFundsException $e) {
            return response()->json((new ApiResponse)->addError(new ErrorApi(422, $e->getMessage())), 422);
        });

        // не прошел валидацию
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                $response = new ApiResponse;
                foreach ($e->validator->errors()->getMessages() as $field => $error) {
                    $response->addError(new ErrorApi($e->status, 'Ошибка валидации', $error[0], $field));
                }

                return response()->json($response, $e->status);
            }
        });
    })->create();
