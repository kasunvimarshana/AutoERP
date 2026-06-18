<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Inventory\Http\Resources\InventoryAdjustmentResource;
use Modules\Inventory\Services\InventoryFacade;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
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

    public function paymentContext(ListPurchaseDocumentRequest $request, PurchasePaymentIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::PAYMENTS_VIEW);

        return response()->json(['data' => $service->context(
            $request->tenantId(),
            $request->organizationUnitId(),
            trim((string) $request->input('search', '')),
            $request->perPage(),
        )]);
    }

    public function createPayment(PreparePurchasePaymentRequest $request, PurchasePaymentIntegrationService $service): PaymentResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::PAYMENTS_EXECUTE);

        $payment = $service->createSupplierPayment(
            tenantId: $request->tenantId(),
            paymentDate: (string) $request->input('payment_date'),
            amount: (string) $request->input('amount'),
            organizationUnitId: $request->organizationUnitId(),
            supplierType: $request->filled('supplier_type') ? (string) $request->input('supplier_type') : null,
            supplierId: $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            currencyId: $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            exchangeRate: (string) $request->input('exchange_rate', '1.000000'),
            referenceNumber: $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            lines: $this->paymentLines($request, $service),
            allocations: array_map(static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
                invoiceId: (int) $row['invoice_id'],
                allocatedAmount: (string) $row['allocated_amount'],
                allocationDate: (string) ($row['allocation_date'] ?? $request->input('payment_date')),
            ), $request->input('allocations', [])),
            createdBy: $request->currentUserId(),
            notes: $request->filled('notes') ? (string) $request->input('notes') : null,
        );

        return new PaymentResource($payment);
    }

    public function preparePayment(PreparePurchasePaymentRequest $request, PurchasePaymentIntegrationService $service): PaymentResource
    {
        return $this->createPayment($request, $service);
    }

    /**
     * @return list<PaymentLineData>
     */
    private function paymentLines(PreparePurchasePaymentRequest $request, PurchasePaymentIntegrationService $service): array
    {
        $lines = [];
        foreach ($request->input('lines', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $accountId = isset($row['source_account_id']) ? (int) $row['source_account_id'] : null;
            $account = $accountId === null ? null : $service->assertPaymentSourceAccount(
                $request->tenantId(),
                $request->organizationUnitId(),
                $accountId,
            );

            $lines[] = new PaymentLineData(
                amount: (string) $row['amount'],
                paymentMethodId: isset($row['payment_method_id']) ? (int) $row['payment_method_id'] : null,
                referenceNumber: isset($row['reference']) ? (string) $row['reference'] : null,
                notes: isset($row['notes']) ? (string) $row['notes'] : null,
                metadata: $accountId === null ? null : ['source_account_id' => $accountId],
                internalBankAccountId: $account !== null && (bool) $account->is_bank_account ? (int) $account->getKey() : null,
                instrumentDirection: (string) ($row['instrument_direction'] ?? 'issued'),
                externalBankName: isset($row['external_bank_name']) ? (string) $row['external_bank_name'] : null,
                externalBankBranch: isset($row['external_bank_branch']) ? (string) $row['external_bank_branch'] : null,
                instrumentNumber: isset($row['instrument_number']) ? (string) $row['instrument_number'] : null,
                instrumentDate: isset($row['instrument_date']) ? (string) $row['instrument_date'] : null,
            );
        }

        return $lines;
    }
}
