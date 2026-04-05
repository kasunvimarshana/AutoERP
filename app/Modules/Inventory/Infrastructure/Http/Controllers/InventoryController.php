<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\IssueStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReceiveStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Application\DTOs\AdjustInventoryData;
use Modules\Inventory\Application\DTOs\IssueStockData;
use Modules\Inventory\Application\DTOs\ReceiveStockData;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryLevelRepositoryInterface $levelRepo,
        private readonly ReceiveStockServiceInterface $receiveService,
        private readonly IssueStockServiceInterface $issueService,
        private readonly AdjustInventoryServiceInterface $adjustService,
        private readonly ReserveStockServiceInterface $reserveService,
        private readonly ReleaseStockServiceInterface $releaseService,
    ) {}

    public function levels(Request $request): JsonResponse
    {
        return response()->json($this->levelRepo->findByWarehouse(
            (int)$request->input('tenant_id'),
            (int)$request->input('warehouse_id')
        ));
    }

    public function receive(Request $request): JsonResponse
    {
        $level = $this->receiveService->execute(ReceiveStockData::fromArray($request->all()));
        return response()->json($level, 201);
    }

    public function issue(Request $request): JsonResponse
    {
        $level = $this->issueService->execute(IssueStockData::fromArray($request->all()));
        return response()->json($level);
    }

    public function adjust(Request $request): JsonResponse
    {
        $level = $this->adjustService->execute(AdjustInventoryData::fromArray($request->all()));
        return response()->json($level);
    }

    public function reserve(Request $request): JsonResponse
    {
        $level = $this->reserveService->execute(
            (int)$request->input('tenant_id'),
            (int)$request->input('product_id'),
            (int)$request->input('warehouse_id'),
            (float)$request->input('quantity'),
        );
        return response()->json($level);
    }

    public function release(Request $request): JsonResponse
    {
        $level = $this->releaseService->execute(
            (int)$request->input('tenant_id'),
            (int)$request->input('product_id'),
            (int)$request->input('warehouse_id'),
            (float)$request->input('quantity'),
        );
        return response()->json($level);
    }
}
