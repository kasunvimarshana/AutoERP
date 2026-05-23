<?php

declare(strict_types=1);

namespace Modules\SystemUser\Presentation\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SystemUser\Domain\Exceptions\SystemUserRecordNotFoundException;

trait HandlesSystemUserHttp
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

    protected function notFound(SystemUserRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }
}
