<?php

declare(strict_types=1);

namespace Modules\UOM\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Result;
use Modules\UOM\Constants\UomCategory;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Constants\UomType;
use Modules\UOM\Contracts\Services\UomUsageSummaryServiceInterface;
use Modules\UOM\Http\Requests\ListUnitOfMeasureRequest;
use Modules\UOM\Http\Requests\StoreUomRequest;
use Modules\UOM\Http\Requests\UpdateUomRequest;
use Modules\UOM\Http\Resources\UnitOfMeasureResource;
use Modules\UOM\Http\Resources\UomLookupResource;
use Modules\UOM\Services\UnitOfMeasures\CreateUnitOfMeasureService;
use Modules\UOM\Services\UnitOfMeasures\DeleteUnitOfMeasureService;
use Modules\UOM\Services\UnitOfMeasures\GetUnitOfMeasureService;
use Modules\UOM\Services\UnitOfMeasures\ListUnitOfMeasuresService;
use Modules\UOM\Services\UnitOfMeasures\UpdateUnitOfMeasureService;
use Modules\UOM\Services\UomLookupService;

final class UnitOfMeasureController extends Controller
{
    public function __construct(
        private readonly ListUnitOfMeasuresService $listService,
        private readonly GetUnitOfMeasureService $getService,
        private readonly CreateUnitOfMeasureService $createService,
        private readonly UpdateUnitOfMeasureService $updateService,
        private readonly DeleteUnitOfMeasureService $deleteService,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly UomUsageSummaryServiceInterface $usageSummary,
        private readonly UomLookupService $lookupService,
    ) {}

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
                $category = (string) ($record->get('category') ?? UomCategory::OTHER);
                $counts[$category] = ($counts[$category] ?? 0) + 1;
            }
        }

        return response()->json([
            'data' => array_map(
                static fn (string $category): array => [
                    'id' => $category,
                    'name' => ucfirst(strtolower(str_replace('_', ' ', $category))),
                    'category' => $category,
                    'unit_count' => $counts[$category] ?? 0,
                ],
                UomCategory::all(),
            ),
        ]);
    }

    public function types(): JsonResponse
    {
        return response()->json(['data' => array_map(
            static fn (string $type): array => ['id' => $type, 'name' => ucfirst($type)],
            UomType::all(),
        )]);
    }

    public function lookup(ListUnitOfMeasureRequest $request): JsonResponse
    {
        return $this->respondLookup($this->lookupService->activeLookup(
            $this->lookupCriteria($request->validated()),
            (int) $request->input('per_page', 20),
            (int) $request->input('page', 1),
        ));
    }

    public function base(ListUnitOfMeasureRequest $request): JsonResponse
    {
        return $this->respondLookup($this->lookupService->baseLookup(
            $this->lookupCriteria($request->validated()),
            (int) $request->input('per_page', 20),
            (int) $request->input('page', 1),
        ));
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
            'meta' => $pageResult->paginationMeta(),
        ]);
    }

    public function show(int|string $id): JsonResponse|UnitOfMeasureResource
    {
        $result = $this->getService->execute($id);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json(['message' => $error->message], $this->errorStatus($error->code));
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
            $error = $result->errorOrFail();

            return response()->json(['message' => $error->message], $this->errorStatus($error->code));
        }

        return response()->json([
            'data' => [
                'unit_id' => (int) $id,
                'counts' => $this->usageSummary->summarize((int) $id, $tenantId),
            ],
        ]);
    }

    public function store(StoreUomRequest $request): JsonResponse|UnitOfMeasureResource
    {
        $result = $this->createService->execute($request->validated());

        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new UnitOfMeasureResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpdateUomRequest $request, int|string $id): JsonResponse|UnitOfMeasureResource
    {
        $result = $this->updateService->execute($id, $request->validated());

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json(['message' => $error->message], $this->errorStatus($error->code));
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
            $error = $result->errorOrFail();

            return response()->json(['message' => $error->message], $this->errorStatus($error->code));
        }

        return response()->json(null, 204);
    }

    private function changeActiveState(int|string $id, bool $isActive): JsonResponse|UnitOfMeasureResource
    {
        $result = $this->updateService->execute($id, ['is_active' => $isActive]);

        if ($result->isFailure()) {
            $error = $result->errorOrFail();

            return response()->json(['message' => $error->message], $this->errorStatus($error->code));
        }

        return new UnitOfMeasureResource($result->valueOrFail());
    }

    private function respondLookup(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        $pageResult = $result->valueOrFail();
        if (! $pageResult instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected lookup response.'], 500);
        }

        return response()->json([
            'data' => UomLookupResource::collection($pageResult->items)->resolve(),
            'meta' => $pageResult->paginationMeta(),
        ]);
    }

    private function lookupCriteria(array $validated): array
    {
        unset($validated['page'], $validated['per_page'], $validated['tenant_id'], $validated['organization_unit_id']);

        return $validated;
    }

    private function errorStatus(string $code): int
    {
        return match ($code) {
            UomErrorCode::FORBIDDEN => 403,
            UomErrorCode::NOT_FOUND => 404,
            default => 422,
        };
    }
}
