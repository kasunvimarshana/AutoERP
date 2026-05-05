<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\ManageStockReorderRuleServiceInterface;
use Modules\Inventory\Domain\Entities\StockReorderRule;
use Modules\Inventory\Infrastructure\Http\Requests\CreateStockReorderRuleRequest;
use Modules\Inventory\Infrastructure\Http\Requests\ListValuationConfigRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateStockReorderRuleRequest;
use Modules\Inventory\Infrastructure\Http\Resources\StockReorderRuleResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StockReorderRuleController extends AuthorizedController
{
    public function __construct(
        private readonly ManageStockReorderRuleServiceInterface $service,
    ) {}

    public function index(ListValuationConfigRequest $request): JsonResponse
    {
        $this->authorize('viewAny', StockReorderRule::class);
        $validated = $request->validated();

        $rules = $this->service->list(
            tenantId: (int) $validated['tenant_id'],
            perPage: (int) ($validated['per_page'] ?? 15),
            page: (int) ($validated['page'] ?? 1),
        );

        return response()->json($rules);
    }

    public function store(CreateStockReorderRuleRequest $request): JsonResponse
    {
        $this->authorize('create', StockReorderRule::class);

        $rule = $this->service->create($request->validated());

        return (new StockReorderRuleResource($rule))
            ->response()
            ->setStatusCode(HttpResponse::HTTP_CREATED);
    }

    public function show(ListValuationConfigRequest $request, int $rule): JsonResponse
    {
        $this->authorize('view', StockReorderRule::class);
        $validated = $request->validated();

        $found = $this->service->find(
            tenantId: (int) $validated['tenant_id'],
            id: $rule,
        );

        return (new StockReorderRuleResource($found))->response();
    }

    public function update(UpdateStockReorderRuleRequest $request, int $rule): JsonResponse
    {
        $this->authorize('update', StockReorderRule::class);
        $validated = $request->validated();

        $updated = $this->service->update(
            tenantId: (int) $validated['tenant_id'],
            id: $rule,
            data: $validated,
        );

        return (new StockReorderRuleResource($updated))->response();
    }

    public function destroy(ListValuationConfigRequest $request, int $rule): JsonResponse
    {
        $this->authorize('delete', StockReorderRule::class);
        $validated = $request->validated();

        $this->service->delete(
            tenantId: (int) $validated['tenant_id'],
            id: $rule,
        );

        return response()->json(null, HttpResponse::HTTP_NO_CONTENT);
    }

    public function lowStock(ListValuationConfigRequest $request): JsonResponse
    {
        $this->authorize('viewAny', StockReorderRule::class);
        $validated = $request->validated();

        $items = $this->service->listLowStock(tenantId: (int) $validated['tenant_id']);

        return response()->json(['data' => $items]);
    }
}
