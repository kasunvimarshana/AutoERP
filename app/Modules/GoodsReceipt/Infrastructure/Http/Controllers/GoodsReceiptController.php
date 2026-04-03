<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\GoodsReceipt\Application\Contracts\ApproveGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CancelGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\CreateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\DeleteGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\FindGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\ReceiveGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\UpdateGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\DTOs\GoodsReceiptData;
use Modules\GoodsReceipt\Application\DTOs\UpdateGoodsReceiptData;
use Modules\GoodsReceipt\Infrastructure\Http\Requests\StoreGoodsReceiptRequest;
use Modules\GoodsReceipt\Infrastructure\Http\Requests\UpdateGoodsReceiptRequest;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptCollection;
use Modules\GoodsReceipt\Infrastructure\Http\Resources\GoodsReceiptResource;

class GoodsReceiptController extends AuthorizedController
{
    public function __construct(
        protected FindGoodsReceiptServiceInterface $findService,
        protected CreateGoodsReceiptServiceInterface $createService,
        protected UpdateGoodsReceiptServiceInterface $updateService,
        protected DeleteGoodsReceiptServiceInterface $deleteService,
        protected ReceiveGoodsReceiptServiceInterface $receiveService,
        protected ApproveGoodsReceiptServiceInterface $approveService,
        protected CancelGoodsReceiptServiceInterface $cancelService,
        protected InspectGoodsReceiptServiceInterface $inspectService,
        protected PutAwayGoodsReceiptServiceInterface $putAwayService,
    ) {}

    public function index(Request $request): GoodsReceiptCollection
    {
        $filters = $request->only(['tenant_id', 'status', 'supplier_id', 'purchase_order_id']);

        return new GoodsReceiptCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreGoodsReceiptRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = GoodsReceiptData::fromArray([
            'tenantId'        => $v['tenant_id'],
            'referenceNumber' => $v['reference_number'],
            'supplierId'      => $v['supplier_id'],
            'purchaseOrderId' => $v['purchase_order_id'] ?? null,
            'warehouseId'     => $v['warehouse_id'] ?? null,
            'receivedDate'    => $v['received_date'] ?? null,
            'currency'        => $v['currency'] ?? 'USD',
            'notes'           => $v['notes'] ?? null,
            'metadata'        => $v['metadata'] ?? null,
            'status'          => $v['status'] ?? 'draft',
            'receivedBy'      => $v['received_by'] ?? null,
        ]);

        $receipt = $this->createService->execute($dto->toArray());

        return (new GoodsReceiptResource($receipt))->response()->setStatusCode(201);
    }

    public function show(int $id): GoodsReceiptResource|JsonResponse
    {
        $receipt = $this->findService->find($id);
        if (! $receipt) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new GoodsReceiptResource($receipt);
    }

    public function update(UpdateGoodsReceiptRequest $request, int $id): GoodsReceiptResource
    {
        $v   = $request->validated();
        $dto = UpdateGoodsReceiptData::fromArray([
            'id'           => $id,
            'notes'        => $v['notes'] ?? null,
            'metadata'     => $v['metadata'] ?? null,
            'receivedDate' => $v['received_date'] ?? null,
            'warehouseId'  => $v['warehouse_id'] ?? null,
        ]);

        return new GoodsReceiptResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Goods receipt deleted successfully']);
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        $receipt = $this->receiveService->execute([
            'id'          => $id,
            'received_by' => $request->integer('received_by'),
        ]);

        return (new GoodsReceiptResource($receipt))->response();
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $receipt = $this->approveService->execute([
            'id'          => $id,
            'approved_by' => $request->integer('approved_by'),
        ]);

        return (new GoodsReceiptResource($receipt))->response();
    }

    public function cancel(int $id): JsonResponse
    {
        $receipt = $this->cancelService->execute(['id' => $id]);

        return (new GoodsReceiptResource($receipt))->response();
    }

    public function inspect(Request $request, int $id): JsonResponse
    {
        $receipt = $this->inspectService->execute([
            'id'           => $id,
            'inspected_by' => $request->integer('inspected_by'),
        ]);

        return (new GoodsReceiptResource($receipt))->response();
    }

    public function putAway(Request $request, int $id): JsonResponse
    {
        $receipt = $this->putAwayService->execute([
            'id'          => $id,
            'put_away_by' => $request->integer('put_away_by'),
        ]);

        return (new GoodsReceiptResource($receipt))->response();
    }
}
