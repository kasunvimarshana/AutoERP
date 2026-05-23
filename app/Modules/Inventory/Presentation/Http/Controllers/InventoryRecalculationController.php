<?php

declare(strict_types=1);

namespace Modules\Inventory\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Exceptions\InventoryRecordNotFoundException;
use Modules\Inventory\Presentation\Http\Resources\InventoryRecordResource;

class InventoryRecalculationController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function stockLevel(int|string $tenant, int|string $stockLevel): InventoryRecordResource|JsonResponse
    {
        try {
            return new InventoryRecordResource($this->inventory->recalculateStockLevel($tenant, $stockLevel));
        } catch (InventoryRecordNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }
}
