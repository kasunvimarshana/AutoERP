<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Finance\Application\Contracts\CreateCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\DeleteCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\FindCostCenterServiceInterface;
use Modules\Finance\Application\Contracts\UpdateCostCenterServiceInterface;
use Modules\Finance\Domain\Entities\CostCenter;
use Modules\Finance\Infrastructure\Http\Requests\ListCostCenterRequest;
use Modules\Finance\Infrastructure\Http\Requests\StoreCostCenterRequest;
use Modules\Finance\Infrastructure\Http\Requests\UpdateCostCenterRequest;
use Modules\Finance\Infrastructure\Http\Resources\CostCenterCollection;
use Modules\Finance\Infrastructure\Http\Resources\CostCenterResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CostCenterController extends AuthorizedController
{
    public function __construct(
        private readonly CreateCostCenterServiceInterface $createCostCenterService,
        private readonly UpdateCostCenterServiceInterface $updateCostCenterService,
        private readonly DeleteCostCenterServiceInterface $deleteCostCenterService,
        private readonly FindCostCenterServiceInterface $findCostCenterService,
    ) {}

    public function index(ListCostCenterRequest $request): JsonResponse
    {
        $this->authorize('viewAny', CostCenter::class);
        $validated = $request->validated();

        $filters = array_filter([
            'tenant_id' => $validated['tenant_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'code' => $validated['code'] ?? null,
            'name' => $validated['name'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $costCenters = $this->findCostCenterService->list(
            filters: $filters,
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
            sort: $validated['sort'] ?? null,
        );

        return (new CostCenterCollection($costCenters))->response();
    }

    public function store(StoreCostCenterRequest $request): JsonResponse
    {
        $this->authorize('create', CostCenter::class);

        $costCenter = $this->createCostCenterService->execute($request->validated());

        return (new CostCenterResource($costCenter))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(Request $request, int $costCenter): CostCenterResource
    {
        $found = $this->findCostCenterOrFail($costCenter);
        $this->authorize('view', $found);

        return new CostCenterResource($found);
    }

    public function update(UpdateCostCenterRequest $request, int $costCenter): CostCenterResource
    {
        $found = $this->findCostCenterOrFail($costCenter);
        $this->authorize('update', $found);

        $payload = $request->validated();
        $payload['id'] = $costCenter;

        return new CostCenterResource($this->updateCostCenterService->execute($payload));
    }

    public function destroy(int $costCenter): JsonResponse
    {
        $found = $this->findCostCenterOrFail($costCenter);
        $this->authorize('delete', $found);

        $this->deleteCostCenterService->execute(['id' => $costCenter]);

        return Response::json(['message' => 'Cost center deleted successfully']);
    }

    private function findCostCenterOrFail(int $id): CostCenter
    {
        $costCenter = $this->findCostCenterService->find($id);

        if (! $costCenter) {
            throw new NotFoundHttpException('Cost center not found.');
        }

        return $costCenter;
    }
}
