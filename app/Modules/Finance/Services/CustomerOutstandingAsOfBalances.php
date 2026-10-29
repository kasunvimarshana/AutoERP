<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Http\Requests\CustomerOutstandingReportRequest;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentSourceType;

final class CustomerOutstandingAsOfBalances
{
    private const PAYMENT_SOURCE_MODULE = 'payment';

    private const SALE_RETURN_SOURCE = 'sale_return';

    public function balances(int $tenantId, string $asOfDate): Builder
    {
        $payments = DB::table('payment_allocations as allocations')
            ->leftJoinSub($this->paymentReversalDates($tenantId), 'payment_reversals', function (JoinClause $join): void {
                $join->on('payment_reversals.allocation_id', '=', 'allocations.id');
            })
            ->where('allocations.tenant_id', $tenantId)
            ->where('allocations.allocation_date', '<=', $asOfDate)
            ->where(function (Builder $query) use ($asOfDate): void {
                $query->where('allocations.status', AllocationStatus::Active->value)
                    ->orWhere(function (Builder $reversed) use ($asOfDate): void {
                        $reversed->where('allocations.status', AllocationStatus::Reversed->value)
                            ->where('payment_reversals.reversal_date', '>', $asOfDate);
                    });
            })
            ->select('allocations.invoice_id')
            ->selectRaw('SUM(allocations.allocated_amount) as paid_amount')
            ->groupBy('allocations.invoice_id');

        $credits = DB::table('invoice_credit_allocations as credits')
            ->join('sale_returns as returns', function (JoinClause $join): void {
                $join->on('returns.id', '=', 'credits.credit_source_id')
                    ->on('returns.tenant_id', '=', 'credits.tenant_id')
                    ->on('returns.invoice_id', '=', 'credits.invoice_id');
            })
            ->where('credits.tenant_id', $tenantId)
            ->where('credits.credit_source_type', self::SALE_RETURN_SOURCE)
            ->where('returns.return_date', '<=', $asOfDate)
            ->select('credits.invoice_id')
            ->selectRaw('SUM(credits.allocated_amount) as credit_allocated_amount')
            ->groupBy('credits.invoice_id');

        return DB::table('invoice_balances as current_balances')
            ->leftJoinSub($payments, 'payments', 'payments.invoice_id', '=', 'current_balances.invoice_id')
            ->leftJoinSub($credits, 'credits', 'credits.invoice_id', '=', 'current_balances.invoice_id')
            ->where('current_balances.tenant_id', $tenantId)
            ->select([
                'current_balances.invoice_id',
                'current_balances.tenant_id',
                'current_balances.organization_unit_id',
                'current_balances.invoice_total',
            ])
            ->selectRaw('COALESCE(payments.paid_amount, 0) as paid_amount')
            ->selectRaw('COALESCE(credits.credit_allocated_amount, 0) as credit_allocated_amount')
            ->selectRaw('(current_balances.invoice_total - COALESCE(payments.paid_amount, 0) - COALESCE(credits.credit_allocated_amount, 0)) as remaining_amount');
    }

    public function invoiceReversalDates(int $tenantId): Builder
    {
        return DB::table('finance_journal_entries as original')
            ->join('finance_journal_entries as reversal', function (JoinClause $join): void {
                $join->on('reversal.reversal_of_id', '=', 'original.id')
                    ->on('reversal.tenant_id', '=', 'original.tenant_id');
            })
            ->where('original.tenant_id', $tenantId)
            ->where('original.source_module', 'invoice')
            ->where('original.source_type', 'invoice')
            ->whereNull('original.reversal_of_id')
            ->select('original.source_id as invoice_id')
            ->selectRaw('MIN(reversal.journal_date) as reversal_date')
            ->groupBy('original.source_id');
    }

    public function assertHistoricalCoverage(CustomerOutstandingReportRequest $request, string $asOfDate, CustomerOutstandingLocationScope $locations): void
    {
        if ($asOfDate >= now()->toDateString()) {
            return;
        }

        $tenantId = $request->tenantId();

        $missingPaymentReversal = DB::table('payment_allocations as allocations')
            ->join('invoices', function (JoinClause $join): void {
                $join->on('invoices.id', '=', 'allocations.invoice_id')
                    ->on('invoices.tenant_id', '=', 'allocations.tenant_id');
            })
            ->leftJoinSub($this->paymentReversalDates($tenantId), 'reversal_dates', 'reversal_dates.allocation_id', '=', 'allocations.id')
            ->where('allocations.tenant_id', $tenantId)
            ->where('invoices.direction', 'outbound')
            ->where('allocations.status', AllocationStatus::Reversed->value)
            ->where('allocations.allocation_date', '<=', $asOfDate)
            ->whereNull('reversal_dates.reversal_date');
        $locations->apply($missingPaymentReversal, $request, 'invoices.organization_unit_id');
        $missingPaymentReversal = $missingPaymentReversal->exists();

        $unknownCredit = DB::table('invoice_credit_allocations as credits')
            ->join('invoices', function (JoinClause $join): void {
                $join->on('invoices.id', '=', 'credits.invoice_id')
                    ->on('invoices.tenant_id', '=', 'credits.tenant_id');
            })
            ->leftJoin('sale_returns as returns', function (JoinClause $join): void {
                $join->on('returns.id', '=', 'credits.credit_source_id')
                    ->on('returns.tenant_id', '=', 'credits.tenant_id')
                    ->on('returns.invoice_id', '=', 'credits.invoice_id');
            })
            ->where('credits.tenant_id', $tenantId)
            ->where('invoices.direction', 'outbound')
            ->where('invoices.invoice_date', '<=', $asOfDate)
            ->where(function (Builder $query): void {
                $query->where('credits.credit_source_type', '!=', self::SALE_RETURN_SOURCE)
                    ->orWhereNull('returns.id');
            });
        $locations->apply($unknownCredit, $request, 'invoices.organization_unit_id');
        $unknownCredit = $unknownCredit->exists();

        $missingInvoiceReversal = DB::table('invoices')
            ->leftJoinSub($this->invoiceReversalDates($tenantId), 'reversal_dates', 'reversal_dates.invoice_id', '=', 'invoices.id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.direction', 'outbound')
            ->where('invoices.status', InvoiceStatus::Reversed->value)
            ->where('invoices.invoice_date', '<=', $asOfDate)
            ->whereNull('reversal_dates.reversal_date');
        $locations->apply($missingInvoiceReversal, $request, 'invoices.organization_unit_id');
        $missingInvoiceReversal = $missingInvoiceReversal->exists();

        $missingCancellationDate = DB::table('invoices')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.direction', 'outbound')
            ->whereIn('invoices.status', [InvoiceStatus::Cancelled->value, InvoiceStatus::Void->value])
            ->where('invoices.invoice_date', '<=', $asOfDate)
            ->whereNull('invoices.cancelled_at');
        $locations->apply($missingCancellationDate, $request, 'invoices.organization_unit_id');
        $missingCancellationDate = $missingCancellationDate->exists();

        if ($missingPaymentReversal || $unknownCredit || $missingInvoiceReversal || $missingCancellationDate) {
            throw ValidationException::withMessages([
                'as_of_date' => ['Historical balance cannot be verified for some older transactions. Select today or reconcile the missing transaction dates.'],
            ]);
        }
    }

    private function paymentReversalDates(int $tenantId): Builder
    {
        return DB::table('finance_journal_entries as original')
            ->join('finance_journal_entries as reversal', function (JoinClause $join): void {
                $join->on('reversal.reversal_of_id', '=', 'original.id')
                    ->on('reversal.tenant_id', '=', 'original.tenant_id');
            })
            ->where('original.tenant_id', $tenantId)
            ->where('original.source_module', self::PAYMENT_SOURCE_MODULE)
            ->where('original.source_type', PaymentSourceType::PaymentAllocation->value)
            ->whereNull('original.reversal_of_id')
            ->select('original.source_id as allocation_id')
            ->selectRaw('MIN(reversal.journal_date) as reversal_date')
            ->groupBy('original.source_id');
    }
}
