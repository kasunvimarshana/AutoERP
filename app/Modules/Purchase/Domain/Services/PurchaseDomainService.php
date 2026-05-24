<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Purchase\Application\Repositories\GrnHeaderRepositoryInterface;
use Modules\Purchase\Application\Repositories\GrnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnLineRepositoryInterface;
use Modules\Purchase\Application\Repositories\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Domain\Aggregates\PurchaseDocumentAggregate;
use Modules\Purchase\Domain\Exceptions\PurchaseIntegrityException;
use Modules\Purchase\Domain\Exceptions\PurchaseRecordNotFoundException;

class PurchaseDomainService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly PurchaseOrderLineRepositoryInterface $purchaseOrderLines,
        private readonly GrnHeaderRepositoryInterface $grnHeaders,
        private readonly GrnLineRepositoryInterface $grnLines,
        private readonly PurchaseReturnRepositoryInterface $purchaseReturns,
        private readonly PurchaseReturnLineRepositoryInterface $purchaseReturnLines,
        private readonly InvoiceRepositoryInterface $invoices,
    ) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (strtolower(trim($resource))) {
            'purchase-orders', 'purchaseorders', 'orders', 'po', 'pos' => 'purchase_orders',
            'purchase-order-lines', 'purchase_orderlines', 'order-lines', 'po-lines' => 'purchase_order_lines',
            'grn-headers', 'grns', 'goods-receipts', 'receipts' => 'grn_headers',
            'grn-lines', 'goods-receipt-lines', 'receipt-lines' => 'grn_lines',
            'purchase-returns', 'returns' => 'purchase_returns',
            'purchase-return-lines', 'return-lines' => 'purchase_return_lines',
            default => str_replace('-', '_', strtolower(trim($resource))),
        };
    }

    public function normalizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function normalizeDecimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('purchase.precision.scale', 4), '.', '');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public function normalizeEnum(string $family, mixed $value, array $allowed, mixed $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw PurchaseIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw PurchaseIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function ensureMutable(string $resource, Model $record, array $definition, bool $updating = false): void
    {
        $immutable = config("purchase.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw PurchaseIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw PurchaseIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantPurchaseOrder(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->purchaseOrders, 'Purchase order', $tenantId, $id);
    }

    public function assertTenantPurchaseOrderLine(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->purchaseOrderLines, 'Purchase order line', $tenantId, $id);
    }

    public function assertTenantGrnHeader(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->grnHeaders, 'GRN header', $tenantId, $id);
    }

    public function assertTenantGrnLine(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->grnLines, 'GRN line', $tenantId, $id);
    }

    public function assertTenantInvoice(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->invoices, 'Invoice', $tenantId, $id);
    }

    public function assertTenantPurchaseReturn(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->purchaseReturns, 'Purchase return', $tenantId, $id);
    }

    public function assertLineBelongsToHeader(Model $line, string $lineColumn, Model $header, string $headerColumn, string $message): void
    {
        if ((string) $line->{$lineColumn} !== (string) $header->{$headerColumn}) {
            throw PurchaseIntegrityException::rule($message);
        }
    }

    public function assertPurchaseReturnLineReferences(Model $returnHeader, ?Model $grnLine, ?Model $purchaseOrderLine): void
    {
        if ($grnLine !== null && $returnHeader->original_grn_id !== null) {
            $this->assertLineBelongsToHeader(
                $grnLine,
                'grn_header_id',
                $returnHeader,
                'original_grn_id',
                'Purchase return line GRN reference must belong to the return header original GRN.',
            );
        }

        if ($purchaseOrderLine !== null && $returnHeader->original_purchase_order_id !== null) {
            $this->assertLineBelongsToHeader(
                $purchaseOrderLine,
                'purchase_order_id',
                $returnHeader,
                'original_purchase_order_id',
                'Purchase return line order reference must belong to the return header original purchase order.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareLineAmounts(array $attributes, string $quantityColumn): array
    {
        $attributes[$quantityColumn] = $this->normalizeDecimal($attributes[$quantityColumn] ?? 0);
        $attributes['unit_price'] = $this->normalizeDecimal($attributes['unit_price'] ?? 0);
        $attributes['tax_amount'] = $this->normalizeDecimal($attributes['tax_amount'] ?? 0);
        $attributes['discount_type'] = $this->normalizeEnum(
            'discount_type',
            $attributes['discount_type'] ?? null,
            config('purchase.discount_types', []),
            config('purchase.discount_types.0', 'fixed'),
        );
        $attributes['discount_value'] = $this->normalizeDecimal($attributes['discount_value'] ?? ($attributes['discount_amount'] ?? 0));

        $grossAmount = (float) $attributes[$quantityColumn] * (float) $attributes['unit_price'];
        $discountAmount = $this->discountAmount($attributes['discount_type'], $attributes['discount_value'], $grossAmount);

        $attributes['gross_amount'] = $this->normalizeDecimal($grossAmount);
        $attributes['discount_amount'] = $this->normalizeDecimal($discountAmount);
        $attributes['line_total'] = $this->normalizeDecimal($grossAmount - $discountAmount);
        $attributes['line_total_with_tax'] = $this->normalizeDecimal((float) $attributes['line_total'] + (float) $attributes['tax_amount']);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    public function calculateDocumentTotals(Collection $lines, array $attributes, bool $includeRestocking = false, bool $includeBalance = false): array
    {
        return (new PurchaseDocumentAggregate(
            $lines,
            $attributes,
            (int) config('purchase.precision.scale', 4),
            (string) config('purchase.calculation.header_discount_base', 'net_after_line_discount'),
        ))->totals($includeRestocking, $includeBalance);
    }

    private function discountAmount(string $type, mixed $value, float $base): float
    {
        $amount = $type === 'percentage'
            ? $base * ((float) ($value ?? 0) / 100)
            : (float) ($value ?? 0);

        return min(max($amount, 0.0), max($base, 0.0));
    }

    private function assertTenantRecord(mixed $repository, string $resource, int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $repository->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw PurchaseRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
