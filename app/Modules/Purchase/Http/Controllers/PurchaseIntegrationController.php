<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Inventory\Http\Resources\InventoryAdjustmentResource;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Purchase\Http\Requests\PreparePurchasePaymentRequest;
use Modules\Purchase\Http\Requests\StorePurchaseInventoryAdjustmentRequest;
use Modules\Purchase\Http\Requests\StorePurchaseInvoiceRequest;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseInvoiceIntegrationService;
use Modules\Purchase\Services\PurchasePaymentIntegrationService;

final class PurchaseIntegrationController
{
    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function createInventoryAdjustmentRequest(StorePurchaseInventoryAdjustmentRequest $request, InventoryFacade $inventory): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::RETURNS_POST);

        return (new InventoryAdjustmentResource($inventory->adjust($request->toData())))
            ->response()
            ->setStatusCode(201);
    }

    public function previewInvoice(StorePurchaseInvoiceRequest $request, PurchaseInvoiceIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW);

        return response()->json(['data' => get_object_vars($service->previewSupplierInvoice($request->toData()))]);
    }

    public function createInvoice(StorePurchaseInvoiceRequest $request, PurchaseInvoiceIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::SUPPLIER_INVOICES_CREATE);

        return response()->json(['data' => $service->createSupplierInvoice($request->toData())], 201);
    }

    public function preparePayment(PreparePurchasePaymentRequest $request, PurchasePaymentIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::PAYMENTS_EXECUTE);

        $data = $service->prepareSupplierPayment(
            tenantId: $request->tenantId(),
            paymentDate: (string) $request->input('payment_date'),
            amount: (string) $request->input('amount'),
            organizationUnitId: $request->organizationUnitId(),
            supplierType: $request->filled('supplier_type') ? (string) $request->input('supplier_type') : null,
            supplierId: $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            currencyId: $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            exchangeRate: (string) $request->input('exchange_rate', '1.000000'),
            referenceNumber: $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            allocations: array_map(static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
                invoiceId: (int) $row['invoice_id'],
                allocatedAmount: (string) $row['allocated_amount'],
                allocationDate: (string) ($row['allocation_date'] ?? $request->input('payment_date')),
            ), $request->input('allocations', [])),
        );

        return response()->json(['data' => $data]);
    }
}
