<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\UOM\Domain\Exceptions\UomIntegrityException;
use Modules\UOM\Domain\Exceptions\UomRecordNotFoundException;

trait HandlesUomHttp
{
    /**
     * @param  array<int, string>  $allowed
     * @return array<string, mixed>
     */
    protected function filters(Request $request, array $allowed): array
    {
        return collect($request->only($allowed))
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->all();
    }

    protected function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    protected function notFound(UomRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }

    protected function unprocessable(UomIntegrityException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
