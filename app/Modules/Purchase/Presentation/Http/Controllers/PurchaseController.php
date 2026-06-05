<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Invoice\Presentation\Http\Resources\InvoiceResource;
use Modules\Purchase\Application\Services\PurchaseService;
use Modules\Purchase\Presentation\Http\Requests\CreatePurchaseInvoiceRequest;
use Modules\Purchase\Presentation\Http\Requests\ListPurchaseRequest;
use Modules\Purchase\Presentation\Http\Requests\LookupPurchaseRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertGrnRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchaseOrderRequest;
use Modules\Purchase\Presentation\Http\Requests\UpsertPurchaseReturnRequest;
use Modules\Purchase\Presentation\Http\Resources\GrnResource;
use Modules\Purchase\Presentation\Http\Resources\PurchaseOrderResource;
use Modules\Purchase\Presentation\Http\Resources\PurchaseReturnResource;

final class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function dashboard(): JsonResponse
    {
        return response()->json(['data' => $this->purchases->dashboard()]);
    }

    public function lookup(LookupPurchaseRequest $request, string $type): JsonResponse
    {
        return response()->json(['data' => $this->purchases->lookup($type, $request->validated())]);
    }

    public function orders(ListPurchaseRequest $request)
    {
        return PurchaseOrderResource::collection($this->purchases->paginateOrders($request->validated()));
    }

    public function showOrder(int $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($this->purchases->findOrder($purchaseOrder));
    }

    public function storeOrder(UpsertPurchaseOrderRequest $request)
    {
        return (new PurchaseOrderResource($this->purchases->createOrder($request->validated())))->response()->setStatusCode(201);
    }

    public function updateOrder(UpsertPurchaseOrderRequest $request, int $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($this->purchases->updateOrder($purchaseOrder, $request->validated()));
    }

    public function confirmOrder(int $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($this->purchases->confirmOrder($purchaseOrder));
    }

    public function cancelOrder(int $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($this->purchases->cancelOrder($purchaseOrder, request('reason')));
    }

    public function closeOrder(int $purchaseOrder): PurchaseOrderResource
    {
        return new PurchaseOrderResource($this->purchases->closeOrder($purchaseOrder, request('reason')));
    }

    public function invoiceOrder(CreatePurchaseInvoiceRequest $request, int $purchaseOrder): InvoiceResource
    {
        return new InvoiceResource($this->purchases->createInvoiceFromOrder($purchaseOrder, $request->validated()));
    }

    public function destroyOrder(int $purchaseOrder): JsonResponse
    {
        $this->purchases->deleteOrder($purchaseOrder);

        return response()->json(null, 204);
    }

    public function grns(ListPurchaseRequest $request)
    {
        return GrnResource::collection($this->purchases->paginateGrns($request->validated()));
    }

    public function showGrn(int $grn): GrnResource
    {
        return new GrnResource($this->purchases->findGrn($grn));
    }

    public function storeGrn(UpsertGrnRequest $request)
    {
        return (new GrnResource($this->purchases->createGrn($request->validated())))->response()->setStatusCode(201);
    }

    public function updateGrn(UpsertGrnRequest $request, int $grn): GrnResource
    {
        return new GrnResource($this->purchases->updateGrn($grn, $request->validated()));
    }

    public function postGrn(int $grn): GrnResource
    {
        return new GrnResource($this->purchases->postGrn($grn));
    }

    public function invoiceGrn(CreatePurchaseInvoiceRequest $request, int $grn): InvoiceResource
    {
        return new InvoiceResource($this->purchases->createInvoiceFromGrn($grn, $request->validated()));
    }

    public function destroyGrn(int $grn): JsonResponse
    {
        $this->purchases->deleteGrn($grn);

        return response()->json(null, 204);
    }

    public function returns(ListPurchaseRequest $request)
    {
        return PurchaseReturnResource::collection($this->purchases->paginateReturns($request->validated()));
    }

    public function showReturn(int $purchaseReturn): PurchaseReturnResource
    {
        return new PurchaseReturnResource($this->purchases->findReturn($purchaseReturn));
    }

    public function storeReturn(UpsertPurchaseReturnRequest $request)
    {
        return (new PurchaseReturnResource($this->purchases->createReturn($request->validated())))->response()->setStatusCode(201);
    }

    public function updateReturn(UpsertPurchaseReturnRequest $request, int $purchaseReturn): PurchaseReturnResource
    {
        return new PurchaseReturnResource($this->purchases->updateReturn($purchaseReturn, $request->validated()));
    }

    public function postReturn(int $purchaseReturn): PurchaseReturnResource
    {
        return new PurchaseReturnResource($this->purchases->postReturn($purchaseReturn));
    }

    public function destroyReturn(int $purchaseReturn): JsonResponse
    {
        $this->purchases->deleteReturn($purchaseReturn);

        return response()->json(null, 204);
    }
}
