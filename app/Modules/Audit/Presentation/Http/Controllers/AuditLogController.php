<?php

declare(strict_types=1);

namespace Modules\Audit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\CreateAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\DeleteAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\GetAuditLogServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\ListAuditLogsServiceInterface;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\UpdateAuditLogServiceInterface;
use Modules\Audit\Presentation\Http\Requests\ListAuditLogRequest;
use Modules\Audit\Presentation\Http\Requests\UpsertAuditLogRequest;
use Modules\Audit\Presentation\Http\Resources\AuditLogResource;
use Modules\Core\Application\DTO\PagedResult;

final class AuditLogController extends Controller
{
    public function __construct(
        private readonly ListAuditLogsServiceInterface $listService,
        private readonly GetAuditLogServiceInterface $getService,
        private readonly CreateAuditLogServiceInterface $createService,
        private readonly UpdateAuditLogServiceInterface $updateService,
        private readonly DeleteAuditLogServiceInterface $deleteService,
    ) {
    }

    public function index(ListAuditLogRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 0);
        $page = (int) ($validated['page'] ?? 0);
        unset($validated['per_page'], $validated['page']);

        $result = $this->listService->execute($validated, $perPage, $page);

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

    public function store(UpsertAuditLogRequest $request): JsonResponse|AuditLogResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new AuditLogResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertAuditLogRequest $request, int|string $id): JsonResponse|AuditLogResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === 'AUDIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new AuditLogResource($result->valueOrFail());
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}