<?php

declare(strict_types=1);

namespace Modules\Manufacturing\Interfaces\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Application\Commands\CreateBomCommand;
use Modules\Manufacturing\Application\Commands\CreateProductionOrderCommand;
use Modules\Manufacturing\Application\Commands\CompleteProductionOrderCommand;
use Modules\Manufacturing\Application\Handlers\CreateBomHandler;
use Modules\Manufacturing\Application\Handlers\CreateProductionOrderHandler;
use Modules\Manufacturing\Application\Handlers\CompleteProductionOrderHandler;
use Modules\Manufacturing\Domain\Contracts\ManufacturingRepositoryInterface;

class ManufacturingController extends Controller
{
    public function __construct(
        private readonly ManufacturingRepositoryInterface $repository,
        private readonly CreateBomHandler                 $createBomHandler,
        private readonly CreateProductionOrderHandler     $createProductionOrderHandler,
        private readonly CompleteProductionOrderHandler   $completeProductionOrderHandler,
    ) {}

    // ── BOM ───────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/manufacturing/boms
     */
    public function listBoms(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $page     = (int) ($request->query('page', 1));
        $perPage  = min((int) ($request->query('per_page', 25)), 100);

        $boms = $this->repository->listBoms($tenantId, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Bills of materials retrieved successfully.',
            'data'    => array_map(fn ($b) => $this->formatBom($b), $boms),
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/manufacturing/boms
     */
    public function createBom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'      => 'required|integer|exists:products,id',
            'variant_id'      => 'nullable|integer|exists:product_variants,id',
            'output_quantity' => 'required|numeric|min:0.0001',
            'reference'       => 'nullable|string|max:100',
            'lines'           => 'required|array|min:1',
            'lines.*.component_product_id' => 'required|integer|exists:products,id',
            'lines.*.component_variant_id' => 'nullable|integer|exists:product_variants,id',
            'lines.*.quantity'             => 'required|numeric|min:0.0001',
            'lines.*.notes'                => 'nullable|string|max:500',
        ]);

        try {
            $bom = $this->createBomHandler->handle(new CreateBomCommand(
                tenantId:       (int) $request->attributes->get('tenant_id'),
                productId:      (int) $validated['product_id'],
                variantId:      isset($validated['variant_id']) ? (int) $validated['variant_id'] : null,
                outputQuantity: (string) $validated['output_quantity'],
                reference:      $validated['reference'] ?? null,
                lines:          $validated['lines'],
                createdBy:      $request->user()?->id ?? 0,
            ));

            return response()->json([
                'success' => true,
                'message' => 'Bill of Materials created successfully.',
                'data'    => $this->formatBom($bom),
                'errors'  => null,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['bom' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * GET /api/v1/manufacturing/boms/{id}
     */
    public function showBom(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        try {
            $bom = $this->repository->findBomById($id, $tenantId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Bill of Materials not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bill of Materials retrieved successfully.',
            'data'    => $this->formatBom($bom),
            'errors'  => null,
        ]);
    }

    // ── Production Orders ─────────────────────────────────────────────────

    /**
     * GET /api/v1/manufacturing/orders
     */
    public function listOrders(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');
        $page     = (int) ($request->query('page', 1));
        $perPage  = min((int) ($request->query('per_page', 25)), 100);

        $orders = $this->repository->listProductionOrders($tenantId, $page, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Production orders retrieved successfully.',
            'data'    => array_map(fn ($o) => $this->formatOrder($o), $orders),
            'errors'  => null,
        ]);
    }

    /**
     * POST /api/v1/manufacturing/orders
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id'      => 'required|integer|exists:products,id',
            'variant_id'      => 'nullable|integer|exists:product_variants,id',
            'warehouse_id'    => 'required|integer|exists:warehouses,id',
            'bom_id'          => 'required|integer|exists:boms,id',
            'planned_quantity' => 'required|numeric|min:0.0001',
            'wastage_percent' => 'nullable|numeric|min:0|max:100',
            'notes'           => 'nullable|string|max:1000',
        ]);

        try {
            $order = $this->createProductionOrderHandler->handle(new CreateProductionOrderCommand(
                tenantId:       (int) $request->attributes->get('tenant_id'),
                productId:      (int) $validated['product_id'],
                variantId:      isset($validated['variant_id']) ? (int) $validated['variant_id'] : null,
                warehouseId:    (int) $validated['warehouse_id'],
                bomId:          (int) $validated['bom_id'],
                plannedQuantity: (string) $validated['planned_quantity'],
                wastagePercent: (string) ($validated['wastage_percent'] ?? '0'),
                notes:          $validated['notes'] ?? null,
                createdBy:      $request->user()?->id ?? 0,
            ));

            return response()->json([
                'success' => true,
                'message' => 'Production order created successfully.',
                'data'    => $this->formatOrder($order),
                'errors'  => null,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['order' => [$e->getMessage()]],
            ], 422);
        }
    }

    /**
     * GET /api/v1/manufacturing/orders/{id}
     */
    public function showOrder(Request $request, int $id): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('tenant_id');

        try {
            $order = $this->repository->findProductionOrderById($id, $tenantId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Production order not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Production order retrieved successfully.',
            'data'    => $this->formatOrder($order),
            'errors'  => null,
        ]);
    }

    /**
     * PATCH /api/v1/manufacturing/orders/{id}/status
     */
    public function updateOrderStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:confirmed,in_progress,completed,cancelled',
        ]);

        $tenantId = (int) $request->attributes->get('tenant_id');

        try {
            $order = $this->repository->updateProductionOrderStatus($id, $tenantId, $validated['status']);

            return response()->json([
                'success' => true,
                'message' => 'Production order status updated successfully.',
                'data'    => $this->formatOrder($order),
                'errors'  => null,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Production order not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        }
    }

    /**
     * PATCH /api/v1/manufacturing/orders/{id}/complete
     */
    public function completeOrder(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'produced_quantity' => 'required|numeric|min:0.0001',
        ]);

        $tenantId = (int) $request->attributes->get('tenant_id');

        try {
            $order = $this->completeProductionOrderHandler->handle(new CompleteProductionOrderCommand(
                tenantId:         $tenantId,
                orderId:          $id,
                producedQuantity: (string) $validated['produced_quantity'],
                completedBy:      $request->user()?->id ?? 0,
            ));

            return response()->json([
                'success' => true,
                'message' => 'Production order completed and stock movements recorded.',
                'data'    => $this->formatOrder($order),
                'errors'  => null,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Production order not found.',
                'data'    => null,
                'errors'  => null,
            ], 404);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'errors'  => ['order' => [$e->getMessage()]],
            ], 422);
        }
    }

    // ── Formatting helpers ────────────────────────────────────────────────

    private function formatBom(\Modules\Manufacturing\Domain\Entities\Bom $bom): array
    {
        return [
            'id'              => $bom->getId(),
            'product_id'      => $bom->getProductId(),
            'variant_id'      => $bom->getVariantId(),
            'output_quantity' => $bom->getOutputQuantity(),
            'reference'       => $bom->getReference(),
            'is_active'       => $bom->isActive(),
            'lines'           => array_map(fn ($l) => [
                'id'                   => $l->getId(),
                'component_product_id' => $l->getComponentProductId(),
                'component_variant_id' => $l->getComponentVariantId(),
                'quantity'             => $l->getQuantity(),
                'notes'                => $l->getNotes(),
            ], $bom->getLines()),
        ];
    }

    private function formatOrder(\Modules\Manufacturing\Domain\Entities\ProductionOrder $order): array
    {
        return [
            'id'               => $order->getId(),
            'reference_no'     => $order->getReferenceNo(),
            'product_id'       => $order->getProductId(),
            'variant_id'       => $order->getVariantId(),
            'warehouse_id'     => $order->getWarehouseId(),
            'bom_id'           => $order->getBomId(),
            'planned_quantity' => $order->getPlannedQuantity(),
            'produced_quantity' => $order->getProducedQuantity(),
            'total_cost'       => $order->getTotalCost(),
            'wastage_percent'  => $order->getWastagePercent(),
            'status'           => $order->getStatus()->value,
            'notes'            => $order->getNotes(),
            'created_by'       => $order->getCreatedBy(),
            'created_at'       => $order->getCreatedAt()->format('Y-m-d\TH:i:sP'),
        ];
    }
}
