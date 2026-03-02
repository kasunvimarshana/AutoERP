<?php

declare(strict_types=1);

namespace App\Shared\Abstractions;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests;

    /**
     * Return a standardized success JSON response.
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
        ?array $meta = null,
    ): \Illuminate\Http\JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
        ];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return a standardized error JSON response.
     */
    protected function error(
        string $message = 'Error',
        ?array $errors = null,
        int $status = 400,
    ): \Illuminate\Http\JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
