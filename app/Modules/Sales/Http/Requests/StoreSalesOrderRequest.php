<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Requests;

use Modules\Sales\DTOs\CreateSalesOrderData;

class StoreSalesOrderRequest extends SalesDocumentRequest
{
    public function rules(): array
    {
        return array_merge($this->documentRules('sales_order_date'), [
            'sales_order_number' => ['nullable', 'string', 'max:100'],
            'quotation_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_id' => ['nullable', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:sales_order_date'],
        ]);
    }

    public function toData(): CreateSalesOrderData
    {
        return new CreateSalesOrderData(
            tenantId: $this->tenantId(),
            salesOrderDate: (string) $this->input('sales_order_date'),
            customerId: (int) $this->input('customer_id'),
            organizationUnitId: $this->organizationUnitId(),
            salesOrderNumber: $this->stringOrNull('sales_order_number'),
            quotationId: $this->intOrNull('quotation_id'),
            warehouseId: $this->intOrNull('warehouse_id'),
            warehouseLocationId: $this->intOrNull('warehouse_location_id'),
            expectedDeliveryDate: $this->stringOrNull('expected_delivery_date'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: $this->lineData(),
            adjustments: $this->adjustmentData(),
        );
    }
}
