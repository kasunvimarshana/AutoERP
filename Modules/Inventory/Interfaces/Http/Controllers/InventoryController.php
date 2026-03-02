<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use DomainException;
use Illuminate\Http\JsonResponse;
use Modules\Inventory\Application\Commands\AdjustStockCommand;
use Modules\Inventory\Application\Commands\ReceiveStockCommand;
use Modules\Inventory\Application\Commands\ReleaseReservationCommand;
use Modules\Inventory\Application\Commands\ReserveStockCommand;
use Modules\Inventory\Application\Commands\ReturnStockCommand;
use Modules\Inventory\Application\Commands\ShipStockCommand;
use Modules\Inventory\Application\Commands\TransferStockCommand;
use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Interfaces\Http\Requests\AdjustStockRequest;
use Modules\Inventory\Interfaces\Http\Requests\ReceiveStockRequest;
use Modules\Inventory\Interfaces\Http\Requests\ReleaseReservationRequest;
use Modules\Inventory\Interfaces\Http\Requests\ReserveStockRequest;
use Modules\Inventory\Interfaces\Http\Requests\ReturnStockRequest;
use Modules\Inventory\Interfaces\Http\Requests\ShipStockRequest;
use Modules\Inventory\Interfaces\Http\Requests\TransferStockRequest;
use Modules\Inventory\Interfaces\Http\Resources\StockBalanceResource;
use Modules\Inventory\Interfaces\Http\Resources\StockLedgerResource;

class InventoryController extends BaseController
{
    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {}

    public function receive(ReceiveStockRequest $request): JsonResponse
    {
        try {
            $entry = $this->inventoryService->receiveStock(new ReceiveStockCommand(
                tenantId: (int) $request->validated('tenant_id'),
                warehouseId: (int) $request->validated('warehouse_id'),
                productId: (int) $request->validated('product_id'),
                quantity: (string) $request->validated('quantity'),
                unitCost: (string) $request->validated('unit_cost'),
                referenceType: $request->validated('reference_type'),
                referenceId: $request->validated('reference_id'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new StockLedgerResource($entry))->resolve(),
                message: 'Stock received successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function adjust(AdjustStockRequest $request): JsonResponse
    {
        try {
            $entry = $this->inventoryService->adjustStock(new AdjustStockCommand(
                tenantId: (int) $request->validated('tenant_id'),
                warehouseId: (int) $request->validated('warehouse_id'),
                productId: (int) $request->validated('product_id'),
                quantity: (string) $request->validated('quantity'),
                unitCost: (string) $request->validated('unit_cost'),
                adjustmentType: $request->validated('adjustment_type'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new StockLedgerResource($entry))->resolve(),
                message: 'Stock adjusted successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function transfer(TransferStockRequest $request): JsonResponse
    {
        try {
            $result = $this->inventoryService->transferStock(new TransferStockCommand(
                tenantId: (int) $request->validated('tenant_id'),
                sourceWarehouseId: (int) $request->validated('source_warehouse_id'),
                destinationWarehouseId: (int) $request->validated('destination_warehouse_id'),
                productId: (int) $request->validated('product_id'),
                quantity: (string) $request->validated('quantity'),
                unitCost: (string) $request->validated('unit_cost'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: [
                    'transfer_out' => (new StockLedgerResource($result['transfer_out']))->resolve(),
                    'transfer_in' => (new StockLedgerResource($result['transfer_in']))->resolve(),
                ],
                message: 'Stock transferred successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function stock(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->inventoryService->listStockBalances($tenantId, $warehouseId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($balance) => (new StockBalanceResource($balance))->resolve(),
                $result['items']
            ),
            message: 'Stock balances retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function ledger(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = (int) request('warehouse_id');
        $productId = (int) request('product_id');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->inventoryService->listLedgerEntries($tenantId, $warehouseId, $productId, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($entry) => (new StockLedgerResource($entry))->resolve(),
                $result['items']
            ),
            message: 'Stock ledger retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function abcAnalysis(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;

        $items = $this->inventoryService->computeAbcAnalysis($tenantId, $warehouseId);

        return $this->success(
            data: $items,
            message: 'ABC analysis computed successfully',
        );
    }

    public function valuation(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;

        $result = $this->inventoryService->computeValuation($tenantId, $warehouseId);

        return $this->success(
            data: $result['items'],
            message: 'Inventory valuation computed successfully',
            meta: ['grand_total_value' => $result['grand_total_value']],
        );
    }

    public function demandForecast(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;
        $periodDays = max(1, (int) request('period_days', 90));

        $items = $this->inventoryService->computeDemandForecast($tenantId, $warehouseId, $periodDays);

        return $this->success(
            data: $items,
            message: 'Demand forecast computed successfully',
        );
    }

    public function turnover(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;
        $periodDays = max(1, (int) request('period_days', 90));

        $items = $this->inventoryService->computeTurnoverRate($tenantId, $warehouseId, $periodDays);

        return $this->success(
            data: $items,
            message: 'Inventory turnover computed successfully',
        );
    }

    public function scan(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $barcode = (string) request('barcode', '');

        if ($barcode === '') {
            return $this->error('The barcode field is required.', status: 422);
        }

        $result = $this->inventoryService->scanByBarcode($tenantId, $barcode);

        if ($result === null) {
            return $this->error('No product found for the given barcode.', status: 404);
        }

        $result['balances'] = array_map(
            fn ($balance) => (new StockBalanceResource($balance))->resolve(),
            $result['balances']
        );

        return $this->success(
            data: $result,
            message: 'Barcode scan successful',
        );
    }

    public function ship(ShipStockRequest $request): JsonResponse
    {
        try {
            $entry = $this->inventoryService->shipStock(new ShipStockCommand(
                tenantId: (int) $request->validated('tenant_id'),
                warehouseId: (int) $request->validated('warehouse_id'),
                productId: (int) $request->validated('product_id'),
                quantity: (string) $request->validated('quantity'),
                unitCost: (string) $request->validated('unit_cost'),
                referenceType: $request->validated('reference_type'),
                referenceId: $request->validated('reference_id'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new StockLedgerResource($entry))->resolve(),
                message: 'Stock shipped successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function reserveStock(ReserveStockRequest $request): JsonResponse
    {
        try {
            $balance = $this->inventoryService->reserveStock(new ReserveStockCommand(
                tenantId: (int) $request->validated('tenant_id'),
                warehouseId: (int) $request->validated('warehouse_id'),
                productId: (int) $request->validated('product_id'),
                quantity: (string) $request->validated('quantity'),
                referenceType: $request->validated('reference_type'),
                referenceId: $request->validated('reference_id'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new StockBalanceResource($balance))->resolve(),
                message: 'Stock reserved successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function releaseStock(ReleaseReservationRequest $request): JsonResponse
    {
        try {
            $balance = $this->inventoryService->releaseReservation(new ReleaseReservationCommand(
                tenantId: (int) $request->validated('tenant_id'),
                warehouseId: (int) $request->validated('warehouse_id'),
                productId: (int) $request->validated('product_id'),
                quantity: (string) $request->validated('quantity'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new StockBalanceResource($balance))->resolve(),
                message: 'Reservation released successfully',
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function returnStock(ReturnStockRequest $request): JsonResponse
    {
        try {
            $entry = $this->inventoryService->returnStock(new ReturnStockCommand(
                tenantId: (int) $request->validated('tenant_id'),
                warehouseId: (int) $request->validated('warehouse_id'),
                productId: (int) $request->validated('product_id'),
                quantity: (string) $request->validated('quantity'),
                unitCost: (string) $request->validated('unit_cost'),
                referenceType: $request->validated('reference_type'),
                referenceId: $request->validated('reference_id'),
                notes: $request->validated('notes'),
            ));

            return $this->success(
                data: (new StockLedgerResource($entry))->resolve(),
                message: 'Stock returned successfully',
                status: 201,
            );
        } catch (DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }
    }

    public function carryingCosts(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $warehouseId = request('warehouse_id') ? (int) request('warehouse_id') : null;
        $periodDays = max(1, (int) request('period_days', 365));
        $carryingRate = request('carrying_rate', '0.25');

        // Clamp carrying rate to a safe range [0.0001, 10.0]
        if (bccomp($carryingRate, '0.0001', 4) < 0) {
            $carryingRate = '0.0001';
        }
        if (bccomp($carryingRate, '10.0', 4) > 0) {
            $carryingRate = '10.0';
        }

        $result = $this->inventoryService->computeCarryingCosts($tenantId, $warehouseId, $periodDays, $carryingRate);

        return $this->success(
            data: $result['items'],
            message: 'Carrying costs computed successfully',
            meta: [
                'grand_total_carrying_cost' => $result['grand_total_carrying_cost'],
                'carrying_rate' => $carryingRate,
                'period_days' => $periodDays,
            ],
        );
    }
}
