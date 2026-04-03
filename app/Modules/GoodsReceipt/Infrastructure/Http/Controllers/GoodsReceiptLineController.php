<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\DeleteGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\FindGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\UpdateGoodsReceiptLineServiceInterface;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptLineData;
use Modules\GoodsReceipt\Application\DTOs\UpdateGoodsReceiptLineData;
use Modules\GoodsReceipt\Infrastructure\Http\Requests\StoreGoodsReceiptLineRequest;
use Modules\GoodsReceipt\Infrastructure\Http\Requests\UpdateGoodsReceiptLineRequest;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptLineCollection;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptLineResource;

class GoodsReceiptLineController extends AuthorizedController
{
    public function __construct(
        protected FindGoodsReceiptLineServiceInterface $findService,
        protected CreateGoodsReceiptLineServiceInterface $createService,
        protected UpdateGoodsReceiptLineServiceInterface $updateService,
        protected DeleteGoodsReceiptLineServiceInterface $deleteService,
    ) {}

    public function index(Request $request): GoodsReceiptLineCollection
    {
        $filters = $request->only(['tenant_id', 'goods_receipt_id']);

        return new GoodsReceiptLineCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreGoodsReceiptLineRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = GoodsReceiptLineData::fromArray([
            'tenantId'           => $v['tenant_id'],
            'goodsReceiptId'     => $v['goods_receipt_id'],
            'lineNumber'         => $v['line_number'],
            'productId'          => $v['product_id'],
            'quantityReceived'   => $v['quantity_received'],
            'unitCost'           => $v['unit_cost'] ?? 0.0,
            'purchaseOrderLineId'=> $v['purchase_order_line_id'] ?? null,
            'variationId'        => $v['variation_id'] ?? null,
            'batchId'            => $v['batch_id'] ?? null,
            'serialNumber'       => $v['serial_number'] ?? null,
            'uomId'              => $v['uom_id'] ?? null,
            'quantityExpected'   => $v['quantity_expected'] ?? 0.0,
            'quantityAccepted'   => $v['quantity_accepted'] ?? 0.0,
            'quantityRejected'   => $v['quantity_rejected'] ?? 0.0,
            'condition'          => $v['condition'] ?? 'good',
            'notes'              => $v['notes'] ?? null,
            'metadata'           => $v['metadata'] ?? null,
            'status'             => $v['status'] ?? 'pending',
            'putawayLocationId'  => $v['putaway_location_id'] ?? null,
        ]);

        $line = $this->createService->execute($dto->toArray());

        return (new GoodsReceiptLineResource($line))->response()->setStatusCode(201);
    }

    public function show(int $id): GoodsReceiptLineResource|JsonResponse
    {
        $line = $this->findService->find($id);
        if (! $line) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new GoodsReceiptLineResource($line);
    }

    public function update(UpdateGoodsReceiptLineRequest $request, int $id): GoodsReceiptLineResource
    {
        $v   = $request->validated();
        $dto = UpdateGoodsReceiptLineData::fromArray([
            'id'               => $id,
            'quantityAccepted' => $v['quantity_accepted'],
            'quantityRejected' => $v['quantity_rejected'],
            'condition'        => $v['condition'],
            'putawayLocationId'=> $v['putaway_location_id'] ?? null,
            'notes'            => $v['notes'] ?? null,
            'metadata'         => $v['metadata'] ?? null,
        ]);

        return new GoodsReceiptLineResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Goods receipt line deleted successfully']);
    }
}
