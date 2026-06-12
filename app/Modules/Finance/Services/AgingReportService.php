<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

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
        $buckets = [
            'current' => '0.000000',
            '1_30' => '0.000000',
            '31_60' => '0.000000',
            '61_90' => '0.000000',
            '90_plus' => '0.000000',
        ];
        $rows = [];

        $query = DB::table('invoice_balances')
            ->join('invoices', 'invoices.id', '=', 'invoice_balances.invoice_id')
            ->where('invoice_balances.tenant_id', $tenantId)
            ->where('invoices.direction', $direction)
            ->whereNotIn('invoices.status', ['cancelled', 'void'])
            ->where('invoice_balances.remaining_amount', '>', '0')
            ->whereDate('invoices.invoice_date', '<=', $asOf->toDateString())
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
            ])
            ->orderBy('invoices.due_date')
            ->orderBy('invoices.invoice_number');

        $organizationUnitId === null
            ? $query->whereNull('invoice_balances.organization_unit_id')
            : $query->where('invoice_balances.organization_unit_id', $organizationUnitId);

        foreach ($query->get() as $row) {
            $remaining = $this->math->normalize((string) $row->remaining_amount);
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
                'invoice_total' => (string) $row->invoice_total,
                'paid_amount' => (string) $row->paid_amount,
                'credit_allocated_amount' => (string) $row->credit_allocated_amount,
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
