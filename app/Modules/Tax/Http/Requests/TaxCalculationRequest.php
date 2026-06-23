<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Tax\DTOs\TaxCalculationData;
use Modules\Tax\DTOs\TaxCalculationLineData;

final class TaxCalculationRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'document_type' => ['required', 'string', 'max:100'],
            'document_date' => ['required', 'date'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'document_tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'header_tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'header_discount_before_tax' => ['nullable', 'decimal:0,6', 'min:0'],
            'header_discount_after_tax' => ['nullable', 'decimal:0,6', 'min:0'],
            'header_charge_before_tax' => ['nullable', 'decimal:0,6', 'min:0'],
            'header_charge_after_tax' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_number' => ['required', 'integer', 'min:1'],
            'lines.*.item_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.tax_group_id' => ['nullable', 'integer', 'min:1', $this->tenantExists('tax_groups', 'id')],
            'lines.*.quantity' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.unit_price' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.taxable_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.discount_before_tax' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.discount_after_tax' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.charge_before_tax' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.charge_after_tax' => ['nullable', 'decimal:0,6', 'min:0'],
        ];
    }

    public function toData(): TaxCalculationData
    {
        $validated = $this->validated();

        return new TaxCalculationData(
            tenantId: $this->tenantId(),
            documentType: (string) $validated['document_type'],
            documentDate: (string) $validated['document_date'],
            organizationUnitId: $this->organizationUnitId(),
            customerId: isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            supplierId: isset($validated['supplier_id']) ? (int) $validated['supplier_id'] : null,
            documentTaxGroupId: isset($validated['document_tax_group_id']) ? (int) $validated['document_tax_group_id'] : null,
            lines: array_map(static fn (array $line): TaxCalculationLineData => new TaxCalculationLineData(
                lineNumber: (int) $line['line_number'],
                quantity: (string) ($line['quantity'] ?? '1.000000'),
                unitPrice: (string) ($line['unit_price'] ?? '0.000000'),
                itemId: isset($line['item_id']) ? (int) $line['item_id'] : null,
                taxGroupId: isset($line['tax_group_id']) ? (int) $line['tax_group_id'] : null,
                discountBeforeTax: (string) ($line['discount_before_tax'] ?? '0.000000'),
                discountAfterTax: (string) ($line['discount_after_tax'] ?? '0.000000'),
                chargeBeforeTax: (string) ($line['charge_before_tax'] ?? '0.000000'),
                chargeAfterTax: (string) ($line['charge_after_tax'] ?? '0.000000'),
                taxableAmount: isset($line['taxable_amount']) ? (string) $line['taxable_amount'] : null,
            ), $validated['lines']),
            headerTaxGroupId: isset($validated['header_tax_group_id']) ? (int) $validated['header_tax_group_id'] : null,
            headerDiscountBeforeTax: (string) ($validated['header_discount_before_tax'] ?? '0.000000'),
            headerDiscountAfterTax: (string) ($validated['header_discount_after_tax'] ?? '0.000000'),
            headerChargeBeforeTax: (string) ($validated['header_charge_before_tax'] ?? '0.000000'),
            headerChargeAfterTax: (string) ($validated['header_charge_after_tax'] ?? '0.000000'),
        );
    }
}
