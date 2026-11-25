<?php

use App\Core\Console\Command\MakeActionCommand;
use App\Core\Console\Command\MakeFeatureCommand;
use App\Core\Exception\BusinessException;
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
    ->withCommands([
        MakeFeatureCommand::class,
        MakeActionCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Lỗi nghiệp vụ là hành vi dự kiến, không ghi vào log lỗi.
        $exceptions->dontReport(BusinessException::class);

        // Chỗ DUY NHẤT đổi lỗi nghiệp vụ sang JSON, cùng format với ApiBehavior::error().
        // Request không cần JSON (vd trang Filament) thì trả null để Laravel xử lý mặc định.
        $exceptions->render(function (BusinessException $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            return response()->json([
                'success' => false,
                'code' => $e->status(),
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], $e->status());
        });
    })->create();
