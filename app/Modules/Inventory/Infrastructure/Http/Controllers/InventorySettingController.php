<?php
namespace Modules\Inventory\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Application\Contracts\CreateInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventorySettingServiceInterface;
use Modules\Inventory\Application\DTOs\InventorySettingData;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySettingRepositoryInterface;
use Modules\Inventory\Infrastructure\Http\Resources\InventorySettingResource;

class InventorySettingController extends Controller
{
    public function __construct(
        private readonly InventorySettingRepositoryInterface $repository,
        private readonly CreateInventorySettingServiceInterface $createService,
        private readonly UpdateInventorySettingServiceInterface $updateService,
    ) {}

    public function show(int $tenantId): JsonResponse
    {
        $setting = $this->repository->findByTenant($tenantId);
        if (!$setting) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new InventorySettingResource($setting));
    }

    public function update(Request $request, int $tenantId): JsonResponse
    {
        $request->validate([
            'valuation_method'        => 'sometimes|string',
            'management_method'       => 'sometimes|string',
            'stock_rotation_strategy' => 'sometimes|string',
            'allocation_algorithm'    => 'sometimes|string',
            'cycle_count_method'      => 'sometimes|string',
            'negative_stock_allowed'  => 'sometimes|boolean',
            'auto_reorder_enabled'    => 'sometimes|boolean',
            'default_reorder_point'   => 'sometimes|nullable|numeric',
            'default_reorder_qty'     => 'sometimes|nullable|numeric',
        ]);

        $setting = $this->repository->findByTenant($tenantId);

        $data = new InventorySettingData(
            tenantId: $tenantId,
            valuationMethod: $request->input('valuation_method', $setting?->valuationMethod ?? 'fifo'),
            managementMethod: $request->input('management_method', $setting?->managementMethod ?? 'standard'),
            stockRotationStrategy: $request->input('stock_rotation_strategy', $setting?->stockRotationStrategy ?? 'fifo'),
            allocationAlgorithm: $request->input('allocation_algorithm', $setting?->allocationAlgorithm ?? 'fifo'),
            cycleCountMethod: $request->input('cycle_count_method', $setting?->cycleCountMethod ?? 'full'),
            negativeStockAllowed: (bool) $request->input('negative_stock_allowed', $setting?->negativeStockAllowed ?? false),
            autoReorderEnabled: (bool) $request->input('auto_reorder_enabled', $setting?->autoReorderEnabled ?? false),
            defaultReorderPoint: $request->input('default_reorder_point', $setting?->defaultReorderPoint),
            defaultReorderQty: $request->input('default_reorder_qty', $setting?->defaultReorderQty),
        );

        if ($setting) {
            $result = $this->updateService->execute($setting, $data);
        } else {
            $result = $this->createService->execute($data);
        }

        return response()->json(new InventorySettingResource($result));
    }
}
