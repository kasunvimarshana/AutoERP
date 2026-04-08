<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\Contracts\CycleCountServiceInterface;
use Modules\Inventory\Infrastructure\Http\Resources\CycleCountLineResource;

class CycleCountLineController extends Controller
{
    public function __construct(
        private readonly CycleCountServiceInterface $cycleCountService,
    ) {}

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $countedQty = (float) $request->input('counted_qty', 0);
        $line = $this->cycleCountService->updateCycleCountLine($tenantId, $id, $countedQty);
        return response()->json(new CycleCountLineResource($line));
    }
}
