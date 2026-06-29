<?php

declare(strict_types=1);

namespace Modules\Sales\Services\Concerns;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCreditProfile;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Invoice\Enums\InvoiceLineType;
use Modules\Invoice\Models\Invoice;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemUnit;
use Modules\Item\Models\ItemUsageRule;
use Modules\Item\Services\ItemPriceResolutionService;
use Modules\Payment\Enums\PaymentMethodDirection;
use Modules\Payment\Models\PaymentMethod;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tax\Models\TaxGroup;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

trait ValidatesFastSales
{
    /** @param list<array<string, mixed>> $lines */
    private function validateMode(array $lines, bool $createOrderOnly, bool $deliverItemsNow, bool $createInvoice, bool $recordReceipt): void
    {
        $hasStock = false;
        $hasNonStock = false;
        foreach ($lines as $line) {
            $hasStock = $hasStock || (bool) $line['is_stock'];
            $hasNonStock = $hasNonStock || ! (bool) $line['is_stock'];
        }
        if ($createOrderOnly && ($deliverItemsNow || $createInvoice || $recordReceipt)) {
            throw new InvalidArgumentException('Order-only mode cannot create delivery, invoice, or receipt documents.');
        }
        if ($recordReceipt && ! $createInvoice) {
            throw new InvalidArgumentException('Customer receipts require customer invoice creation.');
        }
        if (! $createOrderOnly && ! $deliverItemsNow && ! $createInvoice) {
            throw new InvalidArgumentException('Fast sales must create a sales order, delivery, or customer invoice.');
        }
        if (! $hasStock && $deliverItemsNow) {
            throw new InvalidArgumentException('Delivery requires at least one stock item.');
        }
        if ($hasStock && ! $createOrderOnly && ! $deliverItemsNow) {
            throw new InvalidArgumentException('Stock lines require delivery now unless you are creating a sales order only.');
        }
        if ($hasNonStock && $deliverItemsNow && ! $createInvoice) {
            throw new InvalidArgumentException('Delivery-only fast sales cannot include non-stock or service lines.');
        }
    }

    /** @param list<array<string, mixed>> $lines */
    private function mode(array $lines, bool $createOrderOnly, bool $deliverItemsNow, bool $createInvoice, bool $recordReceipt): string
    {
        $hasStock = collect($lines)->contains(fn (array $line): bool => (bool) $line['is_stock']);
        $hasNonStock = collect($lines)->contains(fn (array $line): bool => ! (bool) $line['is_stock']);
        if ($createOrderOnly) {
            return 'order_only';
        }
        if ($deliverItemsNow && ! $createInvoice) {
            return 'delivery_only';
        }
        if ($hasNonStock && ! $hasStock) {
            return $recordReceipt ? 'direct_sale_paid' : 'direct_sale';
        }
        if ($recordReceipt) {
            return $hasNonStock ? 'mixed_cash_sale' : 'cash_sale';
        }
        return $hasNonStock ? 'mixed_credit_sale' : 'credit_sale';
    }

    /** @param array<string, string> $summary */
    private function validateCredit(Customer $customer, array $summary, string $receivedTotal): void
    {
        $balanceDue = $this->math->sub($summary['grand_total'], $receivedTotal);
        if ($this->math->isZero($balanceDue)) {
            return;
        }
        if (! (bool) $customer->is_credit_allowed) {
            throw new InvalidArgumentException('Customer is not enabled for credit sales.');
        }
        $profile = $customer->creditProfile;
        if ($profile instanceof CustomerCreditProfile
            && (bool) $profile->is_active
            && ! (bool) $profile->allow_partial_payment
            && $this->math->compare($receivedTotal, '0.000000') > 0
            && $this->math->compare($receivedTotal, $summary['grand_total']) < 0) {
            throw new InvalidArgumentException('Customer credit profile does not allow partial receipts.');
        }
        $creditLimit = $profile instanceof CustomerCreditProfile && (bool) $profile->is_active
            ? $this->math->normalize((string) $profile->credit_limit)
            : $this->math->normalize((string) ($customer->credit_limit ?? '0.000000'));
        $allowOverCredit = $profile instanceof CustomerCreditProfile
            && (bool) $profile->is_active
            && (bool) $profile->allow_over_credit;
        if ($allowOverCredit || $this->math->isZero($creditLimit)) {
            return;
        }
        $outstanding = Invoice::query()
            ->where('tenant_id', (int) $customer->tenant_id)
            ->when(
                $customer->organization_unit_id === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where('organization_unit_id', $customer->organization_unit_id),
            )
            ->where('invoice_type', 'sales')
            ->where('direction', 'outbound')
            ->where('party_type', self::CUSTOMER_TYPE)
            ->where('party_id', (int) $customer->getKey())
            ->whereNotIn('status', ['cancelled', 'void'])
            ->sum('balance_due');
        $projected = $this->math->add((string) $outstanding, $balanceDue);
        if ($this->math->compare($projected, $creditLimit) > 0) {
            throw new InvalidArgumentException('Customer credit limit would be exceeded by this fast sale.');
        }
    }

    private function isStockItem(Item $item): bool
    {
        $type = $item->item_type instanceof ItemType ? $item->item_type : ItemType::from((string) $item->item_type);
        return (bool) $item->is_stockable && ! in_array($type, [ItemType::NonStock, ItemType::Service, ItemType::Labour], true);
    }

    private function invoiceLineType(Item $item): InvoiceLineType
    {
        $type = $item->item_type instanceof ItemType ? $item->item_type : ItemType::from((string) $item->item_type);
        return match ($type) {
            ItemType::Service => InvoiceLineType::Service,
            ItemType::Labour => InvoiceLineType::Labour,
            default => InvoiceLineType::Item,
        };
    }

    private function assertSalesUsage(Item $item, ?int $organizationUnitId): void
    {
        $rules = ItemUsageRule::query()
            ->where('tenant_id', (int) $item->tenant_id)
            ->where('item_id', (int) $item->getKey())
            ->where('is_enabled', true)
            ->when(
                $organizationUnitId === null,
                fn ($query) => $query->whereNull('organization_unit_id'),
                fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                    $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
                }),
            )
            ->pluck('module_code');
        if ($rules->isNotEmpty() && ! $rules->contains('sales')) {
            throw new InvalidArgumentException('Sales item is not enabled for the sales module.');
        }
    }

    /** @param array<string, mixed> $line */
    private function resolveUomId(int $tenantId, ?int $organizationUnitId, Item $item, array $line): int
    {
        $requested = $this->nullableInt($line['uom_id'] ?? null);
        if ($requested !== null) {
            return $requested;
        }
        foreach ([ItemUnitRole::Sales, ItemUnitRole::Service] as $role) {
            $unit = ItemUnit::query()
                ->where('tenant_id', $tenantId)
                ->where('item_id', $item->getKey())
                ->where('unit_role', $role->value)
                ->where('is_active', true)
                ->when($organizationUnitId === null, fn ($query) => $query->whereNull('organization_unit_id'), fn ($query) => $query->where(function ($scope) use ($organizationUnitId): void {
                    $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
                }))
                ->orderByDesc('is_default')
                ->first();
            if ($unit instanceof ItemUnit) {
                return (int) $unit->uom_id;
            }
        }
        $baseUomId = (int) ($item->base_uom_id ?: 0);
        if ($baseUomId < 1) {
            throw new InvalidArgumentException('Sales item requires a UOM.');
        }
        return $baseUomId;
    }

    private function priceContext(Item $item): string
    {
        $type = $item->item_type instanceof ItemType ? $item->item_type : ItemType::from((string) $item->item_type);
        return in_array($type, [ItemType::Service, ItemType::Labour], true)
            ? ItemPriceResolutionService::CONTEXT_SERVICE
            : ItemPriceResolutionService::CONTEXT_SALES;
    }

    private function customer(int $tenantId, ?int $organizationUnitId, int $customerId, bool $lockRecords): Customer
    {
        $customer = Customer::query()
            ->with(['creditProfile', 'defaultCurrency'])
            ->when($lockRecords, fn ($query) => $query->lockForUpdate())
            ->findOrFail($customerId);
        $this->validator->assertTenantOrg((int) $customer->tenant_id, $customer->organization_unit_id, $tenantId, $organizationUnitId);
        if ((string) $this->enumValue($customer->status) !== 'active') {
            throw new InvalidArgumentException('Sales customer must be active.');
        }
        return $customer;
    }

    private function item(int $tenantId, ?int $organizationUnitId, int $itemId, bool $lockRecords): Item
    {
        $item = Item::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($itemId);
        return $this->validator->item($tenantId, $organizationUnitId, (int) $item->getKey());
    }

    private function warehouse(int $tenantId, ?int $organizationUnitId, int $warehouseId, bool $lockRecords): WarehouseModel
    {
        $warehouse = WarehouseModel::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($warehouseId);
        return $this->validator->warehouse($tenantId, $organizationUnitId, (int) $warehouse->getKey());
    }

    private function warehouseLocation(int $tenantId, ?int $organizationUnitId, int $warehouseId, int $locationId, bool $lockRecords): WarehouseLocationModel
    {
        $location = WarehouseLocationModel::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($locationId);
        return $this->validator->warehouseLocation($tenantId, $organizationUnitId, $warehouseId, (int) $location->getKey());
    }

    /** @param array<string, mixed> $payload */
    private function currencyId(array $payload, Customer $customer, int $tenantId, ?int $organizationUnitId, bool $lockRecords): ?int
    {
        $currencyId = $this->nullableInt($payload['currency_id'] ?? null) ?? $this->nullableInt($customer->default_currency_id);
        if ($currencyId === null) {
            return null;
        }
        CurrencyModel::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($currencyId);
        $this->validator->currency($tenantId, $organizationUnitId, (int) $currencyId);
        return (int) $currencyId;
    }

    private function defaultTaxGroupId(Item $item): ?int
    {
        if ((bool) $item->is_tax_exempt) {
            return null;
        }
        return $this->nullableInt($item->sales_tax_group_id) ?? $this->nullableInt($item->default_tax_group_id);
    }

    private function taxGroup(int $tenantId, ?int $organizationUnitId, int $taxGroupId, bool $lockRecords): TaxGroup
    {
        $group = TaxGroup::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($taxGroupId);
        $this->validator->assertTenantOrg((int) $group->tenant_id, $group->organization_unit_id, $tenantId, $organizationUnitId);
        if (! (bool) $group->active) {
            throw new InvalidArgumentException('Tax group must be active.');
        }
        return $group;
    }

    private function paymentMethod(int $tenantId, ?int $organizationUnitId, int $methodId, bool $lockRecords): PaymentMethod
    {
        $method = PaymentMethod::query()->when($lockRecords, fn ($query) => $query->lockForUpdate())->findOrFail($methodId);
        if ((int) $method->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Payment method belongs to a different tenant.');
        }
        if ($method->organization_unit_id !== null && (int) $method->organization_unit_id !== (int) $organizationUnitId) {
            throw new InvalidArgumentException('Payment method belongs to a different organization unit.');
        }
        if (! (bool) $method->is_active) {
            throw new InvalidArgumentException('Payment method is inactive.');
        }
        $direction = $method->direction_allowed instanceof PaymentMethodDirection
            ? $method->direction_allowed
            : PaymentMethodDirection::from((string) $method->direction_allowed);
        if (! in_array($direction, [PaymentMethodDirection::Inbound, PaymentMethodDirection::Both], true)) {
            throw new InvalidArgumentException('Payment method does not support inbound receipts.');
        }
        return $method;
    }

    private function lockStockBalance(int $tenantId, ?int $organizationUnitId, int $itemId, int $warehouseId, ?int $variantId, ?int $warehouseLocationId, bool $lockRecords): void
    {
        if (! $lockRecords) {
            return;
        }
        InventoryStockBalance::query()
            ->where('tenant_id', $tenantId)
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('item_variant_id', $variantId)
            ->where('warehouse_location_id', $warehouseLocationId)
            ->lockForUpdate()
            ->get();
    }

    /** @param array<string, mixed> $payload */
    private function rejectClientAuthorityFields(array $payload): void
    {
        foreach (['subtotal', 'discount_total', 'tax_total', 'withholding_total', 'grand_total', 'received_total', 'balance_due', 'status', 'posting_status', 'approval_status', 'finance_account_id', 'receivable_account_id', 'revenue_account_id', 'inventory_account_id', 'cost_of_goods_sold_account_id', 'base_quantity', 'base_uom_quantity', 'available_stock', 'available_quantity'] as $key) {
            if (array_key_exists($key, $payload)) {
                throw new InvalidArgumentException('Fast sales totals, statuses, stock, quantities, and finance accounts are server controlled.');
            }
        }
        foreach (($payload['lines'] ?? []) as $line) {
            if (! is_array($line)) {
                continue;
            }
            foreach (['line_total', 'tax_amount', 'withholding_amount', 'base_quantity', 'base_uom_quantity', 'available_stock', 'available_quantity', 'finance_account_id', 'status', 'source_line_type', 'source_line_id'] as $key) {
                if (array_key_exists($key, $line)) {
                    throw new InvalidArgumentException('Fast sales line totals, tax, stock, statuses, base quantities, and source references are server controlled.');
                }
            }
        }
    }

    private function dueDate(string $transactionDate, string $paymentTerms, mixed $explicitDueDate): string
    {
        if ($explicitDueDate !== null && trim((string) $explicitDueDate) !== '') {
            return (string) $explicitDueDate;
        }
        if (preg_match('/(\d+)/', $paymentTerms, $matches) === 1) {
            return CarbonImmutable::parse($transactionDate)->addDays((int) $matches[1])->toDateString();
        }
        return $transactionDate;
    }

    /** @param array<string, mixed> $payload */
    private function notes(array $payload): ?string
    {
        $notes = trim((string) ($payload['notes'] ?? ''));
        foreach ([
            'Customer reference' => trim((string) ($payload['customer_reference'] ?? '')),
            'Payment terms' => trim((string) ($payload['payment_terms'] ?? '')),
        ] as $label => $value) {
            if ($value !== '') {
                $notes = trim($notes."\n{$label}: {$value}");
            }
        }
        return $notes !== '' ? $notes : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $resolved = trim((string) ($value ?? ''));
        return $resolved !== '' ? $resolved : null;
    }
}
