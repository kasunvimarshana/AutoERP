<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnHeaderRepositoryInterface;
use Modules\Sales\Application\Repositories\GdnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Domain\Aggregates\SalesDocumentAggregate;
use Modules\Sales\Domain\Exceptions\SalesIntegrityException;
use Modules\Sales\Domain\Exceptions\SalesRecordNotFoundException;

class SalesDomainService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $salesOrders,
        private readonly SalesOrderLineRepositoryInterface $salesOrderLines,
        private readonly GdnHeaderRepositoryInterface $gdnHeaders,
        private readonly GdnLineRepositoryInterface $gdnLines,
        private readonly SalesReturnRepositoryInterface $salesReturns,
        private readonly SalesReturnLineRepositoryInterface $salesReturnLines,
        private readonly InvoiceRepositoryInterface $invoices,
    ) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (strtolower(trim($resource))) {
            'sales-orders', 'salesorders', 'orders', 'so', 'sos' => 'sales_orders',
            'sales-order-lines', 'sales_orderlines', 'order-lines', 'so-lines' => 'sales_order_lines',
            'gdn-headers', 'gdns', 'goods-deliveries', 'deliveries' => 'gdn_headers',
            'gdn-lines', 'goods-delivery-lines', 'delivery-lines' => 'gdn_lines',
            'sales-returns', 'returns' => 'sales_returns',
            'sales-return-lines', 'return-lines' => 'sales_return_lines',
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
        return number_format((float) ($value ?? 0), (int) config('sales.precision.scale', 4), '.', '');
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
            throw SalesIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw SalesIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
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
        $immutable = config("sales.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw SalesIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw SalesIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantSalesOrder(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->salesOrders, 'Sales order', $tenantId, $id);
    }

    public function assertTenantSalesOrderLine(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->salesOrderLines, 'Sales order line', $tenantId, $id);
    }

    public function assertTenantGdnHeader(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->gdnHeaders, 'GDN header', $tenantId, $id);
    }

    public function assertTenantGdnLine(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->gdnLines, 'GDN line', $tenantId, $id);
    }

    public function assertTenantInvoice(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->invoices, 'Invoice', $tenantId, $id);
    }

    public function assertTenantSalesReturn(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->salesReturns, 'Sales return', $tenantId, $id);
    }

    public function assertLineBelongsToHeader(Model $line, string $lineColumn, Model $header, string $headerColumn, string $message): void
    {
        if ((string) $line->{$lineColumn} !== (string) $header->{$headerColumn}) {
            throw SalesIntegrityException::rule($message);
        }
    }

    public function assertSalesReturnLineReferences(Model $returnHeader, ?Model $gdnLine, ?Model $salesOrderLine): void
    {
        if ($gdnLine !== null && $returnHeader->original_gdn_id !== null) {
            $this->assertLineBelongsToHeader(
                $gdnLine,
                'gdn_header_id',
                $returnHeader,
                'original_gdn_id',
                'Sales return line GDN reference must belong to the return header original GDN.',
            );
        }

        if ($salesOrderLine !== null && $returnHeader->original_sales_order_id !== null) {
            $this->assertLineBelongsToHeader(
                $salesOrderLine,
                'sales_order_id',
                $returnHeader,
                'original_sales_order_id',
                'Sales return line order reference must belong to the return header original sales order.',
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
            config('sales.discount_types', []),
            config('sales.discount_types.0', 'fixed'),
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
        return (new SalesDocumentAggregate(
            $lines,
            $attributes,
            (int) config('sales.precision.scale', 4),
            (string) config('sales.calculation.header_discount_base', 'net_after_line_discount'),
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
            throw SalesRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
