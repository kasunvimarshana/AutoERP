<?php

declare(strict_types=1);

namespace Modules\Audit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\GetAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\ListAuditLogsServiceInterface;
use Modules\Audit\Application\DTOs\AuditLogQueryData;
use Modules\Audit\Presentation\Http\Requests\ListAuditLogRequest;
use Modules\Audit\Presentation\Http\Resources\AuditLogResource;
use Modules\Core\Application\DTO\PagedResult;

final class AuditLogController extends Controller
{
    public function __construct(
        private readonly ListAuditLogsServiceInterface $listService,
        private readonly GetAuditLogServiceInterface $getService,
    ) {
    }

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
