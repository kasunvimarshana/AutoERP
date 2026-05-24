<?php

declare(strict_types=1);

namespace Modules\Invoice\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Domain\Exceptions\InvoiceIntegrityException;
use Modules\Invoice\Domain\Exceptions\InvoiceRecordNotFoundException;

class InvoiceDomainService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly InvoiceReferenceRepositoryInterface $references,
    ) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (strtolower(trim($resource))) {
            'invoice-references', 'invoice_references', 'references' => 'references',
            'invoice-lines', 'invoice_lines', 'lines' => 'lines',
            default => strtolower(trim($resource)),
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
        return number_format((float) ($value ?? 0), (int) config('invoice.precision.scale', 4), '.', '');
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
            throw InvoiceIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw InvoiceIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
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
        $immutable = config("invoice.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw InvoiceIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw InvoiceIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantInvoice(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $invoice = $this->invoices->findForTenantById($tenantId, $id);

        if ($invoice === null) {
            throw InvoiceRecordNotFoundException::for('Invoice', $id);
        }

        return $invoice;
    }

    public function assertTenantReference(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $reference = $this->references->findForTenantById($tenantId, $id);

        if ($reference === null) {
            throw InvoiceRecordNotFoundException::for('Invoice reference', $id);
        }

        return $reference;
    }

    public function assertReferenceBelongsToInvoice(Model $reference, int|string $invoiceId): void
    {
        if ((string) $reference->invoice_id !== (string) $invoiceId) {
            throw InvoiceIntegrityException::rule('Invoice reference must belong to the same invoice as the invoice line.');
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareLineAmounts(array $attributes): array
    {
        $attributes['quantity'] = $this->normalizeDecimal($attributes['quantity'] ?? 0);
        $attributes['unit_price'] = $this->normalizeDecimal($attributes['unit_price'] ?? 0);
        $attributes['tax_amount'] = $this->normalizeDecimal($attributes['tax_amount'] ?? 0);
        $attributes['discount_type'] = $this->normalizeEnum(
            'discount_type',
            $attributes['discount_type'] ?? null,
            config('invoice.discount_types', []),
            config('invoice.discount_types.0', 'fixed')
        );
        $attributes['discount_value'] = $this->normalizeDecimal($attributes['discount_value'] ?? ($attributes['discount_amount'] ?? 0));
        $attributes['discount_amount'] = $this->normalizeDecimal(
            $this->discountAmount(
                $attributes['discount_type'],
                $attributes['discount_value'],
                $this->lineGross($attributes['quantity'], $attributes['unit_price'])
            )
        );
        $attributes['gross_amount'] = $this->normalizeDecimal($this->lineGross($attributes['quantity'], $attributes['unit_price']));
        $attributes['line_total'] = $this->normalizeDecimal(
            (float) $attributes['gross_amount'] - (float) $attributes['discount_amount']
        );
        $attributes['line_total_with_tax'] = $this->normalizeDecimal(
            (float) $attributes['line_total'] + (float) $attributes['tax_amount']
        );

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    public function calculateDocumentTotals(Collection $lines, array $attributes): array
    {
        $subtotal = 0.0;
        $lineDiscountTotal = 0.0;
        $lineTaxTotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += $this->lineGross($line->quantity, $line->unit_price);
            $lineDiscountTotal += (float) ($line->discount_amount ?? 0);
            $lineTaxTotal += (float) ($line->tax_amount ?? 0);
        }

        $headerBase = $this->headerDiscountBase($subtotal, $lineDiscountTotal, $lineTaxTotal);
        $headerDiscountAmount = $this->discountAmount(
            (string) ($attributes['header_discount_type'] ?? config('invoice.discount_types.0', 'fixed')),
            $attributes['header_discount_value'] ?? ($attributes['header_discount_amount'] ?? 0),
            $headerBase
        );

        $totals = [
            'subtotal' => $this->normalizeDecimal($subtotal),
            'line_discount_total' => $this->normalizeDecimal($lineDiscountTotal),
            'line_tax_total' => $this->normalizeDecimal($lineTaxTotal),
            'header_discount_amount' => $this->normalizeDecimal($headerDiscountAmount),
            'header_tax_amount' => $this->normalizeDecimal($attributes['header_tax_amount'] ?? 0),
            'debit_note_total' => $this->normalizeDecimal($attributes['debit_note_total'] ?? 0),
            'credit_note_total' => $this->normalizeDecimal($attributes['credit_note_total'] ?? 0),
            'paid_amount' => $this->normalizeDecimal($attributes['paid_amount'] ?? 0),
        ];

        $totals['discount_total'] = $this->normalizeDecimal(
            (float) $totals['line_discount_total'] + (float) $totals['header_discount_amount']
        );
        $totals['tax_total'] = $this->normalizeDecimal(
            (float) $totals['line_tax_total'] + (float) $totals['header_tax_amount']
        );
        $totals['grand_total'] = $this->normalizeDecimal($this->grandTotal($totals));
        $totals['balance'] = $this->normalizeDecimal(
            (float) $totals['grand_total'] - (float) $totals['paid_amount']
        );

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function grandTotal(array $attributes): float
    {
        return (float) ($attributes['subtotal'] ?? 0)
            - (float) ($attributes['line_discount_total'] ?? 0)
            - (float) ($attributes['header_discount_amount'] ?? 0)
            + (float) ($attributes['line_tax_total'] ?? 0)
            + (float) ($attributes['header_tax_amount'] ?? 0)
            + (float) ($attributes['debit_note_total'] ?? 0)
            - (float) ($attributes['credit_note_total'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function paymentStatus(Model $invoice, array $attributes): string
    {
        $current = (string) ($attributes['status'] ?? $invoice->status ?? config('invoice.statuses.0', 'draft'));

        if (! (bool) config('invoice.calculation.recalculate_status_from_paid_amount', true)) {
            return $current;
        }

        if (in_array($current, config('invoice.calculation.manual_statuses', []), true)) {
            return $current;
        }

        $grandTotal = $this->grandTotal($attributes);
        $paidAmount = (float) ($attributes['paid_amount'] ?? 0);

        if ($grandTotal > 0 && $paidAmount >= $grandTotal) {
            return config('invoice.statuses.3', 'paid');
        }

        if ($paidAmount > 0) {
            return config('invoice.statuses.2', 'partially_paid');
        }

        return $current === config('invoice.statuses.3', 'paid') || $current === config('invoice.statuses.2', 'partially_paid')
            ? config('invoice.statuses.1', 'approved')
            : $current;
    }

    public function lineGross(string|int|float|null $quantity, string|int|float|null $unitPrice): float
    {
        return (float) ($quantity ?? 0) * (float) ($unitPrice ?? 0);
    }

    private function discountAmount(string $type, string|int|float|null $value, float $base): float
    {
        $amount = $type === 'percentage'
            ? $base * ((float) ($value ?? 0) / 100)
            : (float) ($value ?? 0);

        return min(max($amount, 0.0), max($base, 0.0));
    }

    private function headerDiscountBase(float $subtotal, float $lineDiscountTotal, float $lineTaxTotal): float
    {
        return match (config('invoice.calculation.header_discount_base', 'net_after_line_discount')) {
            'gross' => $subtotal,
            'with_line_tax' => $subtotal - $lineDiscountTotal + $lineTaxTotal,
            default => $subtotal - $lineDiscountTotal,
        };
    }
}
