<?php

declare(strict_types=1);

namespace App\Core\Trait;

use Illuminate\Http\JsonResponse;

trait ApiBehavior
{
    public function success(mixed $data, int $code = 200, string $message = 'success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], status: $code);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public function error(int $code = 400, string $message = 'error', array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ], status: $code);
    }
}
