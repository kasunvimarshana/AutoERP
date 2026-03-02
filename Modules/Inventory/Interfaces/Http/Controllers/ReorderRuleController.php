<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Application\Commands\CreateReorderRuleCommand;
use Modules\Inventory\Application\Commands\UpdateReorderRuleCommand;
use Modules\Inventory\Application\Services\ReorderRuleService;
use Modules\Inventory\Interfaces\Http\Requests\CreateReorderRuleRequest;
use Modules\Inventory\Interfaces\Http\Requests\UpdateReorderRuleRequest;
use Modules\Inventory\Interfaces\Http\Resources\ReorderRuleResource;

class ReorderRuleController extends BaseController
{
    public function __construct(
        private readonly ReorderRuleService $reorderRuleService,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $productId = request('product_id') ? (int) request('product_id') : null;
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;
        $activeOnly = filter_var(request('active_only', false), FILTER_VALIDATE_BOOLEAN);
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->reorderRuleService->listRules($tenantId, $productId, $warehouseId, $activeOnly, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($rule) => (new ReorderRuleResource($rule))->resolve(),
                $result['items']
            ),
            message: 'Reorder rules retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateReorderRuleRequest $request): JsonResponse
    {
        try {
            $rule = $this->reorderRuleService->createRule(new CreateReorderRuleCommand(
                tenantId: (int) $request->validated('tenant_id'),
                productId: (int) $request->validated('product_id'),
                warehouseId: (int) $request->validated('warehouse_id'),
                reorderPoint: (string) $request->validated('reorder_point'),
                reorderQuantity: (string) $request->validated('reorder_quantity'),
                isActive: (bool) ($request->validated('is_active') ?? true),
            ));

            return $this->success(
                data: (new ReorderRuleResource($rule))->resolve(),
                message: 'Reorder rule created successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $rule = $this->reorderRuleService->getRule($tenantId, $id);

        if ($rule === null) {
            return $this->error('Reorder rule not found', status: 404);
        }

        return $this->success(
            data: (new ReorderRuleResource($rule))->resolve(),
            message: 'Reorder rule retrieved successfully',
        );
    }

    public function update(UpdateReorderRuleRequest $request, int $id): JsonResponse
    {
        try {
            $rule = $this->reorderRuleService->updateRule(new UpdateReorderRuleCommand(
                tenantId: (int) $request->validated('tenant_id'),
                id: $id,
                reorderPoint: (string) $request->validated('reorder_point'),
                reorderQuantity: (string) $request->validated('reorder_quantity'),
                isActive: (bool) $request->validated('is_active'),
            ));

            return $this->success(
                data: (new ReorderRuleResource($rule))->resolve(),
                message: 'Reorder rule updated successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $tenantId = (int) request('tenant_id');
            $this->reorderRuleService->deleteRule($tenantId, $id);

            return $this->success(data: null, message: 'Reorder rule deleted successfully');
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }

    public function lowStock(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->reorderRuleService->listLowStockItems($tenantId, $warehouseId, $page, $perPage);

        return $this->success(
            data: array_map(fn ($item) => [
                'rule' => (new ReorderRuleResource($item['rule']))->resolve(),
                'quantity_on_hand' => $item['quantity_on_hand'],
            ], $result['items']),
            message: 'Low stock items retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }
}
