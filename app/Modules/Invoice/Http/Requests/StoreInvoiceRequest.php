<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Invoice\DTOs\ManualInvoiceData;
use Modules\Invoice\DTOs\ManualInvoiceLineData;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Supplier\Enums\SupplierStatus;

final class StoreInvoiceRequest extends TenantScopedRequest
{
    private const IDEMPOTENCY_KEY_MAX_LENGTH = 255;

    /** @var list<string> */
    private const USER_LINE_TYPES = [
        InvoiceLineType::Item->value,
        InvoiceLineType::Service->value,
        InvoiceLineType::Labour->value,
        InvoiceLineType::Charge->value,
        InvoiceLineType::Manual->value,
    ];

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['required', 'integer', 'min:1'],
            'direction' => ['required', Rule::enum(InvoiceDirection::class)],
            'invoice_date' => ['required', 'date'],
            'customer_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:direction,'.InvoiceDirection::Outbound->value,
                'prohibited_if:direction,'.InvoiceDirection::Inbound->value,
                $this->activePartyExists('customers', CustomerStatus::Active->value),
            ],
            'supplier_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:direction,'.InvoiceDirection::Inbound->value,
                'prohibited_if:direction,'.InvoiceDirection::Outbound->value,
                $this->activePartyExists('suppliers', SupplierStatus::Active->value),
            ],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('currencies', 'id')->where('is_active', true),
            ],
            'exchange_rate' => ['nullable', 'decimal:0,6', 'gt:0'],
            'document_tax_group_id' => [
                'nullable',
                'integer',
                'min:1',
                $this->activeScopedExists('tax_groups', 'active', softDeletes: false),
            ],
            'notes' => ['nullable', 'string'],
            'supply_date' => ['nullable', 'date'],
            'supply_period_start' => ['nullable', 'date', 'required_with:supply_period_end'],
            'supply_period_end' => ['nullable', 'date', 'required_with:supply_period_start', 'after_or_equal:supply_period_start'],
            'place_of_supply' => ['nullable', 'string', 'max:1000'],
            'payment_mode' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => $this->routeIs('api.v1.invoices.store')
                ? ['required', 'string', 'max:'.self::IDEMPOTENCY_KEY_MAX_LENGTH]
                : ['prohibited'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_price' => ['required', 'decimal:0,6', 'min:0'],
            'lines.*.line_type' => ['nullable', Rule::in(self::USER_LINE_TYPES)],
            'lines.*.item_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_if:lines.*.line_type,'.InvoiceLineType::Item->value,
                $this->activeScopedExists('items', 'is_active'),
            ],
            'lines.*.uom_id' => [
                'nullable',
                'integer',
                'min:1',
                $this->activeScopedExists('unit_of_measures', 'is_active'),
            ],
            'lines.*.tax_group_id' => [
                'nullable',
                'integer',
                'min:1',
                $this->activeScopedExists('tax_groups', 'active', softDeletes: false),
            ],
            'lines.*.discount_amount' => ['nullable', 'decimal:0,6', 'min:0'],
            'lines.*.charge_amount' => ['nullable', 'decimal:0,6', 'min:0'],

            'invoice_type' => ['prohibited'],
            'invoice_number' => ['prohibited'],
            'status' => ['prohibited'],
            'party_type' => ['prohibited'],
            'party_id' => ['prohibited'],
            'sources' => ['prohibited'],
            'source_lines' => ['prohibited'],
            'adjustments' => ['prohibited'],
            'lines.*.line_number' => ['prohibited'],
            'lines.*.tax_amount' => ['prohibited'],
            'lines.*.line_total' => ['prohibited'],
            'lines.*.source_line_type' => ['prohibited'],
            'lines.*.source_line_id' => ['prohibited'],
            'lines.*.metadata' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->routeIs('api.v1.invoices.store')) {
            $this->merge([
                'idempotency_key' => $this->input('idempotency_key') ?: $this->header('Idempotency-Key'),
            ]);
        }
    }

    public function toData(): ManualInvoiceData
    {
        return new ManualInvoiceData(
            tenantId: $this->tenantId(),
            direction: InvoiceDirection::from((string) $this->input('direction')),
            invoiceDate: (string) $this->input('invoice_date'),
            organizationUnitId: (int) $this->organizationUnitId(),
            customerId: $this->intOrNull('customer_id'),
            supplierId: $this->intOrNull('supplier_id'),
            dueDate: $this->stringOrNull('due_date'),
            currencyId: $this->intOrNull('currency_id'),
            exchangeRate: (string) $this->input('exchange_rate', '1.000000'),
            documentTaxGroupId: $this->intOrNull('document_tax_group_id'),
            notes: $this->stringOrNull('notes'),
            createdBy: $this->currentUserId(),
            lines: array_map($this->mapLine(...), $this->input('lines')),
            supplyDate: $this->stringOrNull('supply_date'),
            supplyPeriodStart: $this->stringOrNull('supply_period_start'),
            supplyPeriodEnd: $this->stringOrNull('supply_period_end'),
            placeOfSupply: $this->stringOrNull('place_of_supply'),
            paymentMode: $this->stringOrNull('payment_mode'),
            paymentTerms: $this->stringOrNull('payment_terms'),
        );
    }

    public function idempotencyKey(): string
    {
        return trim((string) $this->input('idempotency_key'));
    }

    private function mapLine(array $row): ManualInvoiceLineData
    {
        return new ManualInvoiceLineData(
            description: (string) $row['description'],
            quantity: (string) $row['quantity'],
            unitPrice: (string) $row['unit_price'],
            lineType: InvoiceLineType::from((string) ($row['line_type'] ?? InvoiceLineType::Manual->value)),
            itemId: isset($row['item_id']) ? (int) $row['item_id'] : null,
            uomId: isset($row['uom_id']) ? (int) $row['uom_id'] : null,
            taxGroupId: isset($row['tax_group_id']) ? (int) $row['tax_group_id'] : null,
            discountAmount: (string) ($row['discount_amount'] ?? '0.000000'),
            chargeAmount: (string) ($row['charge_amount'] ?? '0.000000'),
        );
    }

    private function activePartyExists(string $table, string $activeStatus): Exists
    {
        return $this->scopedExists($table)
            ->where('status', $activeStatus)
            ->whereNull('deleted_at');
    }

    private function activeScopedExists(
        string $table,
        string $activeColumn,
        bool $softDeletes = true,
    ): Exists {
        $rule = $this->scopedExists($table)->where($activeColumn, true);

        return $softDeletes ? $rule->whereNull('deleted_at') : $rule;
    }

    private function scopedExists(string $table): Exists
    {
        $organizationUnitId = $this->organizationUnitId();

        return $this->tenantExists($table)->where(
            static fn ($query) => $query
                ->whereNull('organization_unit_id')
                ->orWhere('organization_unit_id', $organizationUnitId),
        );
    }

    private function intOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }

    private function stringOrNull(string $key): ?string
    {
        return $this->filled($key) ? trim((string) $this->input($key)) : null;
    }
}
