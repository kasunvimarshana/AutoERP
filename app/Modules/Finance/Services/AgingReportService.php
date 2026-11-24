<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\Services;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;

final class AgingReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return array<string, mixed>
     */
    public function receivables(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $asOfDate = null,
    ): array {
        return $this->calculate($tenantId, $organizationUnitId, 'outbound', $asOfDate);
    }

    /**
     * @return array<string, mixed>
     */
    public function payables(
        int $tenantId,
        ?int $organizationUnitId,
        ?string $asOfDate = null,
    ): array {
        return $this->calculate($tenantId, $organizationUnitId, 'inbound', $asOfDate);
    }

    /**
     * @return array<string, mixed>
     */
    public function calculate(
        int $tenantId,
        ?int $organizationUnitId,
        string $direction,
        ?string $asOfDate = null,
    ): array {
        $asOf = Carbon::parse($asOfDate ?: now()->toDateString())->startOfDay();
        $asOfDateStr = $asOf->toDateString();
        $buckets = [
            'current' => '0.000000',
            '1_30' => '0.000000',
            '31_60' => '0.000000',
            '61_90' => '0.000000',
            '90_plus' => '0.000000',
        ];
        $rows = [];

        $paymentReversals = DB::table('finance_journal_entries as original')
            ->join('finance_journal_entries as reversal', function (JoinClause $join): void {
                $join->on('reversal.reversal_of_id', '=', 'original.id')
                    ->on('reversal.tenant_id', '=', 'original.tenant_id');
            })
            ->where('original.tenant_id', $tenantId)
            ->where('original.source_module', 'payment')
            ->where('original.source_type', 'payment_allocation')
            ->whereNull('original.reversal_of_id')
            ->select('original.source_id as allocation_id')
            ->selectRaw('MIN(reversal.journal_date) as reversal_date')
            ->groupBy('original.source_id');

        $allocationsByInvoice = DB::table('payment_allocations as allocations')
            ->leftJoinSub($paymentReversals, 'payment_reversals', 'payment_reversals.allocation_id', '=', 'allocations.id')
            ->where('allocations.tenant_id', $tenantId)
            ->whereDate('allocations.allocation_date', '<=', $asOfDateStr)
            ->where(function ($q) use ($asOfDateStr): void {
                $q->where('allocations.status', 'active')
                    ->orWhere(function ($q2) use ($asOfDateStr): void {
                        $q2->where('allocations.status', 'reversed')
                            ->where(function ($q3) use ($asOfDateStr): void {
                                $q3->where('payment_reversals.reversal_date', '>', $asOfDateStr)
                                    ->orWhere(function ($q4) use ($asOfDateStr): void {
                                        $q4->whereNull('payment_reversals.reversal_date')
                                            ->whereDate('allocations.updated_at', '>', $asOfDateStr);
                                    });
                            });
                    });
            })
            ->groupBy('allocations.invoice_id')
            ->select('allocations.invoice_id', DB::raw('SUM(allocations.allocated_amount) as total_paid'))
            ->pluck('total_paid', 'allocations.invoice_id');

        $creditsByInvoice = DB::table('invoice_credit_allocations as credits')
            ->leftJoin('sale_returns as returns', function (JoinClause $join): void {
                $join->on('returns.id', '=', 'credits.credit_source_id')
                    ->on('returns.tenant_id', '=', 'credits.tenant_id');
            })
            ->leftJoin('invoices as credit_invoices', function (JoinClause $join): void {
                $join->on('credit_invoices.id', '=', 'credits.credit_source_id')
                    ->on('credit_invoices.tenant_id', '=', 'credits.tenant_id');
            })
            ->where('credits.tenant_id', $tenantId)
            ->where(function ($query) use ($asOfDateStr): void {
                $query->whereDate('returns.return_date', '<=', $asOfDateStr)
                    ->orWhere(function ($q) use ($asOfDateStr): void {
                        $q->whereNull('returns.return_date')
                            ->whereDate('credit_invoices.invoice_date', '<=', $asOfDateStr);
                    })
                    ->orWhere(function ($q) use ($asOfDateStr): void {
                        $q->whereNull('returns.return_date')
                            ->whereNull('credit_invoices.invoice_date')
                            ->whereDate('credits.created_at', '<=', $asOfDateStr);
                    });
            })
            ->groupBy('credits.invoice_id')
            ->select('credits.invoice_id', DB::raw('SUM(credits.allocated_amount) as total_credit'))
            ->pluck('total_credit', 'credits.invoice_id');

        $adjustmentsByInvoice = DB::table('invoice_adjustment_allocations as adjustments')
            ->leftJoin('invoices', function (JoinClause $join): void {
                $join->on('invoices.id', '=', 'adjustments.invoice_id')
                    ->on('invoices.tenant_id', '=', 'adjustments.tenant_id');
            })
            ->where('adjustments.tenant_id', $tenantId)
            ->where(function ($query) use ($asOfDateStr): void {
                $query->whereDate('invoices.invoice_date', '<=', $asOfDateStr)
                    ->orWhere(function ($q) use ($asOfDateStr): void {
                        $q->whereNull('invoices.invoice_date')
                            ->whereDate('adjustments.created_at', '<=', $asOfDateStr);
                    });
            })
            ->groupBy('adjustments.invoice_id')
            ->select('adjustments.invoice_id', DB::raw('SUM(adjustments.allocated_amount) as total_adj'))
            ->pluck('total_adj', 'adjustments.invoice_id');

        $invoiceReversals = DB::table('finance_journal_entries as original')
            ->join('finance_journal_entries as reversal', function (JoinClause $join): void {
                $join->on('reversal.reversal_of_id', '=', 'original.id')
                    ->on('reversal.tenant_id', '=', 'original.tenant_id');
            })
            ->where('original.tenant_id', $tenantId)
            ->where('original.source_module', 'invoice')
            ->where('original.source_type', 'invoice')
            ->whereNull('original.reversal_of_id')
            ->select('original.source_id as invoice_id', DB::raw('MIN(reversal.journal_date) as reversal_date'))
            ->groupBy('original.source_id')
            ->pluck('reversal_date', 'invoice_id');

        $query = DB::table('invoice_balances')
            ->join('invoices', 'invoices.id', '=', 'invoice_balances.invoice_id')
            ->where('invoice_balances.tenant_id', $tenantId)
            ->where('invoices.direction', $direction)
            ->whereNotIn('invoices.status', ['cancelled', 'void'])
            ->whereDate('invoices.invoice_date', '<=', $asOfDateStr)
            ->select([
                'invoice_balances.invoice_id',
                'invoice_balances.invoice_total',
                'invoice_balances.paid_amount',
                'invoice_balances.credit_allocated_amount',
                'invoice_balances.remaining_amount',
                'invoice_balances.status',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.party_type',
                'invoices.party_id',
                'invoices.status as invoice_status',
                'invoices.updated_at as invoice_updated_at',
            ])
            ->orderBy('invoices.due_date')
            ->orderBy('invoices.invoice_number');

        $organizationUnitId === null
            ? $query->whereNull('invoice_balances.organization_unit_id')
            : $query->where('invoice_balances.organization_unit_id', $organizationUnitId);

        foreach ($query->get() as $row) {
            if ($row->invoice_status === 'reversed') {
                $reversalDate = $invoiceReversals[$row->invoice_id]
                    ?? Carbon::parse($row->invoice_updated_at)->toDateString();
                if ($reversalDate <= $asOfDateStr) {
                    continue;
                }
            }

            $paidAmount = $this->math->normalize((string) ($allocationsByInvoice[$row->invoice_id] ?? '0.000000'));
            $creditAmount = $this->math->normalize($this->math->add(
                (string) ($creditsByInvoice[$row->invoice_id] ?? '0.000000'),
                (string) ($adjustmentsByInvoice[$row->invoice_id] ?? '0.000000'),
            ));
            $settled = $this->math->add($paidAmount, $creditAmount);
            $invoiceTotal = $this->math->normalize((string) $row->invoice_total);
            $remaining = $this->math->sub($invoiceTotal, $settled);

            if ($this->math->compare($remaining, '0.000000') <= 0) {
                continue;
            }

            $dueDate = Carbon::parse($row->due_date ?: $row->invoice_date)->startOfDay();
            $daysOverdue = $dueDate->greaterThanOrEqualTo($asOf)
                ? 0
                : (int) $dueDate->diffInDays($asOf);
            $bucket = $this->bucket($daysOverdue);
            $buckets[$bucket] = $this->math->add($buckets[$bucket], $remaining);

            $rows[] = [
                'invoice_id' => (int) $row->invoice_id,
                'invoice_number' => (string) $row->invoice_number,
                'invoice_date' => Carbon::parse($row->invoice_date)->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'party_type' => $row->party_type,
                'party_id' => $row->party_id,
                'invoice_total' => $invoiceTotal,
                'paid_amount' => $paidAmount,
                'credit_allocated_amount' => $creditAmount,
                'remaining_amount' => $remaining,
                'status' => (string) $row->status,
                'days_overdue' => $daysOverdue,
                'bucket' => $bucket,
            ];
        }

        return [
            'as_of_date' => $asOf->toDateString(),
            'direction' => $direction,
            'buckets' => $buckets,
            'total' => $this->math->sum(array_values($buckets)),
            'rows' => $rows,
        ];
    }

    private function bucket(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue <= 0 => 'current',
            $daysOverdue <= 30 => '1_30',
            $daysOverdue <= 60 => '31_60',
            $daysOverdue <= 90 => '61_90',
            default => '90_plus',
        };
    }
}
