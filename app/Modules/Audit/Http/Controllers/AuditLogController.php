<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Audit\DTOs\AuditLogQueryData;
use Modules\Audit\Http\Requests\ListAuditLogRequest;
use Modules\Audit\Http\Resources\AuditLogResource;
use Modules\Audit\Services\AuditLogs\GetAuditLogService;
use Modules\Audit\Services\AuditLogs\ListAuditLogsService;
use Modules\Core\DTOs\PagedResult;

final class AuditLogController extends Controller
{
    public function __construct(
        private readonly ListAuditLogsService $listService,
        private readonly GetAuditLogService $getService,
    ) {}

    public function index(ListAuditLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->listService->execute(AuditLogQueryData::fromArray($validated));

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => AuditLogResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|AuditLogResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new AuditLogResource($result->valueOrFail());
    }
}
