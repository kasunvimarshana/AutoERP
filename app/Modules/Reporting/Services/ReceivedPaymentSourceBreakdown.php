<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceDirection;
use Modules\Invoice\Enums\InvoiceType;
use Modules\Payment\Enums\AllocationStatus;

final class ReceivedPaymentSourceBreakdown
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  Collection<int, object>  $paymentLines
     * @return array<string, array{selected_invoices: string, earlier_invoices: string, other_invoices: string, unapplied: string}>
     */
    public function run(Collection $paymentLines, int $tenantId, ?int $organizationUnitId, string $dateFrom, string $dateTo): array
    {
        $emptyTotals = [
            'selected_invoices' => '0.000000',
            'earlier_invoices' => '0.000000',
            'other_invoices' => '0.000000',
            'unapplied' => '0.000000',
        ];
        $totals = [];
        $byPayment = $paymentLines->groupBy('payment_id');
        if ($byPayment->isEmpty()) {
            return $totals;
        }

        $allocations = DB::table('payment_allocations as allocations')
            ->leftJoin('invoices', function ($join) use ($tenantId): void {
                $join->on('invoices.id', '=', 'allocations.invoice_id')
                    ->where('invoices.tenant_id', '=', $tenantId);
            })
            ->where('allocations.tenant_id', $tenantId)
            ->where('allocations.status', AllocationStatus::Active->value)
            ->whereIn('allocations.payment_id', $byPayment->keys()->all())
            ->when($organizationUnitId === null,
                fn ($query) => $query->whereNull('allocations.organization_unit_id'),
                fn ($query) => $query->where('allocations.organization_unit_id', $organizationUnitId),
            )
            ->orderBy('allocations.id')
            ->get([
                'allocations.payment_id',
                'allocations.allocated_amount',
                'allocations.invoice_date_snapshot',
                'invoices.direction',
                'invoices.invoice_type',
            ])->groupBy('payment_id');

        foreach ($byPayment as $paymentId => $lines) {
            $first = $lines->first();
            if ($first === null) {
                continue;
            }

            $paymentTotal = (string) $first->payment_total;
            foreach ($lines->groupBy(fn (object $line): string => $line->type."\0".$line->name) as $methodKey => $methodLines) {
                $methodAmount = $this->math->sum($methodLines->pluck('amount')->map(fn ($amount): string => (string) $amount));
                $methodBaseAmount = $this->math->mul($methodAmount, (string) $first->exchange_rate);
                $remainingAmount = $methodAmount;
                $remainingBase = $methodBaseAmount;
                $totals[$methodKey] ??= $emptyTotals;

                foreach ($allocations->get($paymentId, collect()) as $allocation) {
                    if ($this->math->compare($paymentTotal, '0') <= 0 || $this->math->compare($remainingAmount, '0') <= 0) {
                        break;
                    }

                    $share = $this->math->div(
                        $this->math->mul((string) $allocation->allocated_amount, $methodAmount, 12),
                        $paymentTotal,
                    );
                    $share = $this->math->compare($share, $remainingAmount) > 0 ? $remainingAmount : $share;
                    $baseShare = $this->math->mul($share, (string) $first->exchange_rate);
                    $baseShare = $this->math->compare($baseShare, $remainingBase) > 0 ? $remainingBase : $baseShare;
                    $category = $this->invoiceCategory($allocation, $dateFrom, $dateTo);

                    $totals[$methodKey][$category] = $this->math->add($totals[$methodKey][$category], $baseShare);
                    $remainingAmount = $this->math->sub($remainingAmount, $share);
                    $remainingBase = $this->math->sub($remainingBase, $baseShare);
                }

                $totals[$methodKey]['unapplied'] = $this->math->add($totals[$methodKey]['unapplied'], $remainingBase);
            }
        }

        return $totals;
    }

    private function invoiceCategory(object $allocation, string $dateFrom, string $dateTo): string
    {
        if ($allocation->direction !== InvoiceDirection::Outbound->value
            || in_array($allocation->invoice_type, [InvoiceType::Credit->value, InvoiceType::Debit->value], true)) {
            return 'other_invoices';
        }

        $date = (string) $allocation->invoice_date_snapshot;
        if ($date < $dateFrom) {
            return 'earlier_invoices';
        }

        return $date <= $dateTo ? 'selected_invoices' : 'other_invoices';
    }
}
