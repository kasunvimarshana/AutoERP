<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\Http\Resources\PaymentResource;
use Modules\Purchase\Http\Requests\ListPurchaseDocumentRequest;
use Modules\Purchase\Http\Requests\PreparePurchasePaymentRequest;
use Modules\Purchase\Http\Requests\StorePurchaseInvoiceRequest;
use Modules\Purchase\Http\Resources\PurchasePaymentPreviewResource;
use Modules\Purchase\Services\PurchaseAuthorizationService;
use Modules\Purchase\Services\PurchaseInvoiceIntegrationService;
use Modules\Purchase\Services\PurchasePaymentIntegrationService;

final class PurchaseIntegrationController
{
    public function __construct(private readonly PurchaseAuthorizationService $authorization) {}

    public function previewInvoice(StorePurchaseInvoiceRequest $request, PurchaseInvoiceIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::SUPPLIER_INVOICES_VIEW);
        $preview = $service->previewSupplierInvoice($request->toData());

        return response()->json(['data' => [
            'subtotal' => $preview->subtotal,
            'discount_total' => $preview->discountTotal,
            'tax_total' => $preview->taxTotal,
            'charge_total' => $preview->chargeTotal,
            'adjustment_total' => $preview->adjustmentTotal,
            'grand_total' => $preview->grandTotal,
            'line_totals' => $preview->lineTotals,
            'header_increase_total' => $preview->headerIncreaseTotal,
            'header_decrease_total' => $preview->headerDecreaseTotal,
        ]]);
    }

    public function createInvoice(StorePurchaseInvoiceRequest $request, PurchaseInvoiceIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::SUPPLIER_INVOICES_CREATE);

        return (new InvoiceResource($service->createSupplierInvoice($request->toData())->load([
            'lines',
            'sources',
            'sourceLines',
            'adjustments',
            'adjustmentAllocations',
            'balance',
        ])))
            ->response()
            ->setStatusCode(201);
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

        return new PaymentResource($service->createSupplierPayment(
            tenantId: $request->tenantId(),
            paymentDate: (string) $request->input('payment_date'),
            amount: (string) $request->input('amount'),
            organizationUnitId: $request->organizationUnitId(),
            supplierType: $request->filled('supplier_type') ? (string) $request->input('supplier_type') : null,
            supplierId: $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            currencyId: $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            exchangeRate: (string) $request->input('exchange_rate', '1.000000'),
            referenceNumber: $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            lines: $this->paymentLines($request),
            allocations: $this->paymentAllocations($request),
            createdBy: $request->currentUserId(),
            notes: $request->filled('notes') ? (string) $request->input('notes') : null,
        ));
    }

    public function preparePayment(PreparePurchasePaymentRequest $request, PurchasePaymentIntegrationService $service): PurchasePaymentPreviewResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), PurchaseAuthorizationService::PAYMENTS_VIEW);

        return new PurchasePaymentPreviewResource($service->previewSupplierPayment(
            tenantId: $request->tenantId(),
            paymentDate: (string) $request->input('payment_date'),
            amount: (string) $request->input('amount'),
            organizationUnitId: $request->organizationUnitId(),
            supplierType: $request->filled('supplier_type') ? (string) $request->input('supplier_type') : null,
            supplierId: $request->filled('supplier_id') ? (int) $request->input('supplier_id') : null,
            currencyId: $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            exchangeRate: (string) $request->input('exchange_rate', '1.000000'),
            referenceNumber: $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            lines: $this->paymentLines($request),
            allocations: $this->paymentAllocations($request),
            createdBy: $request->currentUserId(),
            notes: $request->filled('notes') ? (string) $request->input('notes') : null,
        ));
    }

    private function paymentLines(PreparePurchasePaymentRequest $request): array
    {
        return array_map(static fn (array $row): PaymentLineData => new PaymentLineData(
            amount: (string) $row['amount'],
            paymentMethodId: (int) $row['payment_method_id'],
            referenceNumber: isset($row['reference']) ? (string) $row['reference'] : null,
            notes: isset($row['notes']) ? (string) $row['notes'] : null,
            instrumentDirection: 'issued',
            externalBankName: isset($row['external_bank_name']) ? (string) $row['external_bank_name'] : null,
            externalBankBranch: isset($row['external_bank_branch']) ? (string) $row['external_bank_branch'] : null,
            instrumentNumber: isset($row['instrument_number']) ? (string) $row['instrument_number'] : null,
            instrumentDate: isset($row['instrument_date']) ? (string) $row['instrument_date'] : null,
        ), $request->input('lines', []));
    }

    private function paymentAllocations(PreparePurchasePaymentRequest $request): array
    {
        return array_map(static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
            invoiceId: (int) $row['invoice_id'],
            allocatedAmount: (string) $row['allocated_amount'],
            allocationDate: (string) ($row['allocation_date'] ?? $request->input('payment_date')),
        ), $request->input('allocations', []));
    }
}
