<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitRecordNotFoundException;

trait HandlesOrganizationUnitHttp
{
    protected function perPage(Request $request): ?int
    {
        return $request->filled('per_page') ? max(1, min((int) $request->integer('per_page'), 100)) : null;
    }

    protected function notFound(OrganizationUnitRecordNotFoundException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 404);
    }
}
