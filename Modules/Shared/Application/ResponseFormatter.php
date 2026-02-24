<?php
namespace Modules\Shared\Application;
use Illuminate\Http\JsonResponse;
class ResponseFormatter
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'errors' => null,
        ], $status);
    }
    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null,
            'meta' => [],
            'errors' => $errors,
        ], $status);
    }
    public static function paginated(\Illuminate\Pagination\LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'errors' => null,
        ]);
    }
}
