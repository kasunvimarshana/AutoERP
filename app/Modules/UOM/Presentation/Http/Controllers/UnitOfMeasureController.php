<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\DTO\PagedResult;
use Modules\UOM\Application\Contracts\Services\UomUsageSummaryServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\CreateUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\DeleteUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\GetUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\ListUnitOfMeasuresServiceInterface;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\UpdateUnitOfMeasureServiceInterface;
use Modules\UOM\Domain\Constants\UomType;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Modules\UOM\Presentation\Http\Requests\ListUnitOfMeasureRequest;
use Modules\UOM\Presentation\Http\Requests\UpsertUnitOfMeasureRequest;
use Modules\UOM\Presentation\Http\Resources\UnitOfMeasureResource;

final class UnitOfMeasureController extends Controller
{
    public function __construct(
        private readonly ListUnitOfMeasuresServiceInterface $listService,
        private readonly GetUnitOfMeasureServiceInterface $getService,
        private readonly CreateUnitOfMeasureServiceInterface $createService,
        private readonly UpdateUnitOfMeasureServiceInterface $updateService,
        private readonly DeleteUnitOfMeasureServiceInterface $deleteService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly UomUsageSummaryServiceInterface $usageSummary,
    ) {
    }

    public function categories(): JsonResponse
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context is required.'], 422);
        }

        $result = $this->listService->execute(['tenant_id' => $tenantId], 0, 0);
        $counts = [];

        if ($result->isSuccess() && $result->valueOrFail() instanceof PagedResult) {
            foreach ($result->valueOrFail()->items as $record) {
                $type = (string) ($record->get('type') ?? $record->get('category') ?? UomType::OTHER);
                $counts[$type] = ($counts[$type] ?? 0) + 1;
            }
        }

        return response()->json([
            'data' => array_map(
                static fn (string $type): array => [
                    'id' => $type,
                    'name' => ucfirst(strtolower(str_replace('_', ' ', $type))),
                    'type' => $type,
                    'unit_count' => $counts[$type] ?? 0,
                ],
                UomType::all(),
            ),
        ]);
    }

    public function index(ListUnitOfMeasureRequest $request): JsonResponse
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
            'data' => UnitOfMeasureResource::collection($pageResult->items)->resolve(),
            'meta' => [
                'total' => $pageResult->total,
                'page' => $pageResult->page,
                'per_page' => $pageResult->perPage,
                'page_count' => $pageResult->pageCount(),
                'has_more' => $pageResult->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse|UnitOfMeasureResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new UnitOfMeasureResource($result->valueOrFail());
    }

    public function usage(int|string $id): JsonResponse
    {
        $tenantId = $this->currentTenant->currentTenantId();
        if ($tenantId === null) {
            return response()->json(['message' => 'Tenant context is required.'], 422);
        }

        $result = $this->getService->execute($id);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json([
            'data' => [
                'unit_id' => (int) $id,
                'counts' => $this->usageSummary->summarize((int) $id, $tenantId),
            ],
        ]);
    }

    public function store(UpsertUnitOfMeasureRequest $request): JsonResponse|UnitOfMeasureResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new UnitOfMeasureResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertUnitOfMeasureRequest $request, int|string $id): JsonResponse|UnitOfMeasureResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new UnitOfMeasureResource($result->valueOrFail());
    }

    public function activate(int|string $id): JsonResponse|UnitOfMeasureResource
    {
        return $this->changeActiveState($id, true);
    }

    public function deactivate(int|string $id): JsonResponse|UnitOfMeasureResource
    {
        return $this->changeActiveState($id, false);
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deleteService->execute($id);

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }

    private function changeActiveState(int|string $id, bool $isActive): JsonResponse|UnitOfMeasureResource
    {
        $result = $this->updateService->execute($id, ['is_active' => $isActive]);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $status = $error->code === UomErrorCode::NOT_FOUND ? 404 : 422;

            return response()->json(['message' => $error->message], $status);
        }

        return new UnitOfMeasureResource($result->valueOrFail());
    }
}
