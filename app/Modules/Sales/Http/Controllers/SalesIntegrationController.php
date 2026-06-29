<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Sales\Http\Requests\PrepareSalesPaymentRequest;
use Modules\Sales\Http\Requests\StoreSalesInvoiceRequest;
use Modules\Sales\Services\SalesAuthorizationService;
use Modules\Sales\Services\SalesInvoiceIntegrationService;
use Modules\Sales\Services\SalesPaymentPreparationService;

final class SalesIntegrationController
{
    public function __construct(private readonly SalesAuthorizationService $authorization) {}

    public function previewInvoice(StoreSalesInvoiceRequest $request, SalesInvoiceIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CUSTOMER_INVOICES_VIEW);

        return response()->json(['data' => get_object_vars($service->previewCustomerInvoice($request->toData()))]);
    }

    public function createInvoice(StoreSalesInvoiceRequest $request, SalesInvoiceIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::CUSTOMER_INVOICES_CREATE);

        return response()->json(['data' => $service->createCustomerInvoice($request->toData())], 201);
    }

    public function preparePayment(PrepareSalesPaymentRequest $request, SalesPaymentPreparationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), SalesAuthorizationService::RECEIPTS_VIEW);

        $data = $service->prepareCustomerReceipt(
            tenantId: $request->tenantId(),
            paymentDate: (string) $request->input('payment_date'),
            amount: (string) $request->input('amount'),
            organizationUnitId: $request->organizationUnitId(),
            customerId: (int) $request->input('customer_id'),
            currencyId: $request->filled('currency_id') ? (int) $request->input('currency_id') : null,
            exchangeRate: (string) $request->input('exchange_rate', '1.000000'),
            referenceNumber: $request->filled('reference_number') ? (string) $request->input('reference_number') : null,
            lines: array_map(static fn (array $row): PaymentLineData => new PaymentLineData(
                amount: (string) $row['amount'],
                paymentMethodId: (int) $row['payment_method_id'],
                referenceNumber: $row['reference_number'] ?? null,
                notes: $row['notes'] ?? null,
                instrumentDirection: 'received',
                externalBankName: $row['external_bank_name'] ?? null,
                externalBankBranch: $row['external_bank_branch'] ?? null,
                instrumentNumber: $row['instrument_number'] ?? null,
                instrumentDate: $row['instrument_date'] ?? null,
            ), $request->input('lines', [])),
            allocations: array_map(static fn (array $row): PaymentAllocationData => new PaymentAllocationData(
                invoiceId: (int) $row['invoice_id'],
                allocatedAmount: (string) $row['allocated_amount'],
                allocationDate: (string) ($row['allocation_date'] ?? $request->input('payment_date')),
            ), $request->input('allocations', [])),
            createdBy: $request->currentUserId(),
            notes: $request->filled('notes') ? (string) $request->input('notes') : null,
        );

        return response()->json(['data' => $data]);
    }
}
