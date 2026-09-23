<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;

final class SalesSettlementBreakdownService
{
    private const FINAL_INVOICE_STATUSES = [
        InvoiceStatus::Posted->value,
        InvoiceStatus::PartiallyPaid->value,
        InvoiceStatus::Paid->value,
    ];

    private const CASH = 'cash';

    private const CARD = 'card';

    private const OTHER_PAID = 'other_paid';

    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return array<string, mixed>
     */
    public function run(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
    ): array {
        $invoiceQuery = $this->salesInvoiceQuery($tenantId, $organizationUnitId, $dateFrom, $dateTo);
        $invoiceTotals = (clone $invoiceQuery)
            ->selectRaw(
                'COALESCE(SUM(balance_due), 0) as credit_amount, '
                .'COUNT(CASE WHEN balance_due > 0 THEN 1 END) as credit_document_count, '
                .'COALESCE(SUM(credit_total), 0) as credits_applied'
            )
            ->first();

        $breakdown = [
            self::CASH => $this->emptyMetric(),
            self::CARD => $this->emptyMetric(),
            self::OTHER_PAID => $this->emptyMetric(),
        ];
        $invoiceIds = [
            self::CASH => [],
            self::CARD => [],
            self::OTHER_PAID => [],
        ];

        foreach ($this->allocationRows($invoiceQuery, $tenantId, $organizationUnitId)->groupBy('allocation_id') as $rows) {
            $this->apportionAllocation($rows, $breakdown, $invoiceIds);
        }

        foreach ($breakdown as $category => $metric) {
            $breakdown[$category]['document_count'] = count($invoiceIds[$category]);
        }

        return [
            'cash' => $breakdown[self::CASH],
            'card' => $breakdown[self::CARD],
            'credit' => [
                'amount' => $this->decimal($invoiceTotals->credit_amount ?? 0),
                'document_count' => (int) ($invoiceTotals->credit_document_count ?? 0),
            ],
            'other_paid' => $breakdown[self::OTHER_PAID],
            'credits_applied' => $this->decimal($invoiceTotals->credits_applied ?? 0),
            'source_note' => 'Cash and card show active receipt allocations to these sales. Split-method receipts are apportioned using their persisted payment-line amounts. On credit is the current invoice balance.',
        ];
    }

    private function salesInvoiceQuery(
        int $tenantId,
        ?int $organizationUnitId,
        string $dateFrom,
        string $dateTo,
    ): Builder {
        $query = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->where('direction', InvoiceDirection::Outbound->value)
            ->whereIn('status', self::FINAL_INVOICE_STATUSES)
            ->whereNotIn('invoice_type', [InvoiceType::Credit->value, InvoiceType::Debit->value])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->whereNull('deleted_at');

        $this->organizationScope($query, 'organization_unit_id', $organizationUnitId);

        return $query;
    }

    /**
     * @return Collection<int, object>
     */
    private function allocationRows(
        Builder $invoiceQuery,
        int $tenantId,
        ?int $organizationUnitId,
    ): Collection {
        $query = DB::table('payment_allocations as allocations')
            ->joinSub(
                (clone $invoiceQuery)->select(['id', 'tenant_id', 'organization_unit_id']),
                'sales',
                'sales.id',
                '=',
                'allocations.invoice_id',
            )
            ->join('payments', 'payments.id', '=', 'allocations.payment_id')
            ->join('payment_lines as lines', 'lines.payment_id', '=', 'payments.id')
            ->where('allocations.tenant_id', $tenantId)
            ->where('payments.tenant_id', $tenantId)
            ->where('lines.tenant_id', $tenantId)
            ->where('allocations.status', AllocationStatus::Active->value)
            ->where('payments.direction', PaymentDirection::Inbound->value)
            ->where('payments.document_status', PaymentDocumentStatus::Approved->value)
            ->where('payments.posting_status', PaymentPostingStatus::Posted->value)
            ->whereNull('payments.deleted_at')
            ->select([
                'allocations.id as allocation_id',
                'allocations.invoice_id',
                'allocations.allocated_amount',
                'payments.total_amount as payment_total',
                'lines.line_number',
                'lines.amount as line_amount',
                'lines.payment_method_type_snapshot as method_type',
            ])
            ->orderBy('allocations.id')
            ->orderBy('lines.line_number');

        $this->organizationScope($query, 'allocations.organization_unit_id', $organizationUnitId);
        $this->organizationScope($query, 'payments.organization_unit_id', $organizationUnitId);
        $this->organizationScope($query, 'lines.organization_unit_id', $organizationUnitId);

        return $query->get();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  array<string, array{amount: string, document_count: int}>  $breakdown
     * @param  array<string, array<int, true>>  $invoiceIds
     */
    private function apportionAllocation(Collection $rows, array &$breakdown, array &$invoiceIds): void
    {
        $rows = $rows->values();
        $first = $rows->first();
        if (! is_object($first)) {
            return;
        }

        $allocated = $this->decimal($first->allocated_amount);
        $paymentTotal = $this->decimal($first->payment_total);
        if ($this->math->compare($allocated, '0') <= 0 || $this->math->compare($paymentTotal, '0') <= 0) {
            return;
        }

        $remaining = $allocated;
        $lastIndex = $rows->count() - 1;

        foreach ($rows as $index => $row) {
            $share = $index === $lastIndex
                ? $remaining
                : $this->math->div(
                    $this->math->mul($allocated, (string) $row->line_amount, 12),
                    $paymentTotal,
                );
            if ($this->math->compare($share, $remaining) > 0) {
                $share = $remaining;
            }

            $category = $this->category((string) $row->method_type);
            $breakdown[$category]['amount'] = $this->math->add($breakdown[$category]['amount'], $share);
            if ($this->math->compare($share, '0') > 0) {
                $invoiceIds[$category][(int) $row->invoice_id] = true;
            }
            $remaining = $this->math->sub($remaining, $share);
        }
    }

    private function category(string $methodType): string
    {
        return match ($methodType) {
            PaymentMethodType::Cash->value => self::CASH,
            PaymentMethodType::Card->value => self::CARD,
            default => self::OTHER_PAID,
        };
    }

    /** @return array{amount: string, document_count: int} */
    private function emptyMetric(): array
    {
        return ['amount' => $this->decimal(0), 'document_count' => 0];
    }

    private function organizationScope(Builder $query, string $column, ?int $organizationUnitId): void
    {
        $organizationUnitId === null
            ? $query->whereNull($column)
            : $query->where($column, $organizationUnitId);
    }

    private function decimal(mixed $value): string
    {
        return $this->math->normalize((string) ($value ?? '0'));
    }
}
