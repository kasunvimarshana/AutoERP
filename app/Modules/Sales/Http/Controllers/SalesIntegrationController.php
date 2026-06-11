<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Sales\Http\Requests\PrepareSalesPaymentRequest;
use Modules\Sales\Http\Requests\StoreSalesInvoiceRequest;
use Modules\Sales\Services\SalesInvoiceIntegrationService;
use Modules\Sales\Services\SalesPaymentPreparationService;

final class SalesIntegrationController
{
    public function previewInvoice(StoreSalesInvoiceRequest $request, SalesInvoiceIntegrationService $service): JsonResponse
    {
        return response()->json(['data' => get_object_vars($service->previewCustomerInvoice($request->toData()))]);
    }

    public function createInvoice(StoreSalesInvoiceRequest $request, SalesInvoiceIntegrationService $service): JsonResponse
    {
        return response()->json(['data' => $service->createCustomerInvoice($request->toData())], 201);
    }

    public function preparePayment(PrepareSalesPaymentRequest $request, SalesPaymentPreparationService $service): JsonResponse
    {
        $data = $service->prepareCustomerReceipt(
            tenantId: $request->tenantId(),
            paymentDate: (string) $request->input('payment_date'),
            amount: (string) $request->input('amount'),
            organizationUnitId: $request->organizationUnitId(),
            customerId: $request->filled('customer_id') ? (int) $request->input('customer_id') : null,
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
