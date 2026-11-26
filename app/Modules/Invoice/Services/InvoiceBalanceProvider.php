<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Contracts\InvoiceBalanceProviderInterface;
use Modules\Invoice\DTOs\BalanceResultData;
use Modules\Invoice\DTOs\InvoiceBalanceResult;
use Modules\Invoice\Enums\InvoiceBalanceStatus;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tenant\Models\TenantModel;

final class InvoiceBalanceProvider implements InvoiceBalanceProviderInterface
{
    public function __construct(
        private readonly InvoiceStatusService $statuses,
        private readonly DecimalMath $math,
    ) {}

    public function getInvoiceReferences(array $invoiceIds): array
    {
        return Invoice::query()
            ->whereIn('id', array_values(array_unique($invoiceIds)))
            ->get(['id', 'invoice_number', 'invoice_date', 'currency_code_snapshot', 'exchange_rate'])
            ->mapWithKeys(fn (Invoice $invoice): array => [
                (int) $invoice->getKey() => [
                    'id' => (int) $invoice->getKey(),
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_date' => $invoice->invoice_date?->toDateString(),
                    'currency_code' => $invoice->currency_code_snapshot,
                    'exchange_rate' => (string) $invoice->exchange_rate,
                    'name' => $invoice->invoice_number ?? 'Invoice',
                ],
            ])
            ->all();
    }

    public function getInvoiceBalance(int $invoiceId): InvoiceBalanceResult
    {
        $invoice = Invoice::query()->with('balance')->findOrFail($invoiceId);
        $balance = $invoice->balance;

        return new InvoiceBalanceResult(
            invoiceId: (int) $invoice->getKey(),
            invoiceTotal: (string) $balance->invoice_total,
            paidAmount: (string) $balance->paid_amount,
            creditAmount: (string) $balance->credit_allocated_amount,
            debitAmount: (string) $balance->debit_allocated_amount,
            refundedAmount: (string) $balance->refunded_amount,
            remainingAmount: (string) $balance->remaining_amount,
            status: $balance->status instanceof InvoiceBalanceStatus
                ? $balance->status
                : InvoiceBalanceStatus::from((string) $balance->status),
        );
    }

    public function getBalance(int $invoiceId): BalanceResultData
    {
        return $this->balanceData(Invoice::query()->with('balance')->findOrFail($invoiceId));
    }

    public function getInvoiceStatus(int $invoiceId): string
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);

        return $invoice->status instanceof \BackedEnum
            ? (string) $invoice->status->value
            : (string) $invoice->status;
    }

    public function getOutstandingTotalsForParties(
        int $tenantId,
        ?int $organizationUnitId,
        string $partyType,
        array $partyIds,
    ): array {
        $partyIds = array_values(array_unique(array_filter(
            array_map(static fn (mixed $partyId): int => (int) $partyId, $partyIds),
            static fn (int $partyId): bool => $partyId > 0,
        )));

        if ($partyIds === []) {
            return [];
        }

        $query = Invoice::query()
            ->join('invoice_balances', 'invoice_balances.invoice_id', '=', 'invoices.id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.party_type', $partyType)
            ->whereIn('invoices.party_id', $partyIds)
            ->where('invoice_balances.remaining_amount', '>', '0')
            ->whereNotIn('invoices.status', [
                InvoiceStatus::Draft->value,
                InvoiceStatus::Reversed->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ]);

        $organizationUnitId === null
            ? $query->whereNull('invoices.organization_unit_id')
            : $query->where('invoices.organization_unit_id', $organizationUnitId);

        $rows = $query
            ->select([
                'invoices.party_id',
                'invoices.currency_code_snapshot',
                'invoices.exchange_rate',
                'invoice_balances.remaining_amount',
            ])
            ->orderBy('invoices.currency_code_snapshot')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $partyId = (int) $row->party_id;
            $code = $row->currency_code_snapshot === null ? null : (string) $row->currency_code_snapshot;
            $codeKey = $code ?? '__null__';
            $remaining = (string) $row->remaining_amount;
            $rate = (string) $row->exchange_rate;
            if ($this->math->isZero($rate) || $this->math->isNegative($rate)) {
                $rate = '1.000000';
            }
            $baseRemaining = $this->math->mul($remaining, $rate);

            if (! isset($grouped[$partyId][$codeKey])) {
                $grouped[$partyId][$codeKey] = [
                    'amount' => '0.000000',
                    'currency_code' => $code,
                    'base_amount' => '0.000000',
                ];
            }

            $grouped[$partyId][$codeKey]['amount'] = $this->math->add(
                $grouped[$partyId][$codeKey]['amount'],
                $remaining,
            );
            $grouped[$partyId][$codeKey]['base_amount'] = $this->math->add(
                $grouped[$partyId][$codeKey]['base_amount'],
                $baseRemaining,
            );
        }

        $result = [];
        foreach ($grouped as $partyId => $currencies) {
            $result[$partyId] = array_values(array_map(fn (array $c): array => [
                'amount' => $this->math->normalize($c['amount']),
                'currency_code' => $c['currency_code'],
                'base_amount' => $this->math->normalize($c['base_amount']),
            ], $currencies));
        }

        return $result;
    }

    public function getPartyExposureInCurrency(
        int $tenantId,
        ?int $organizationUnitId,
        string $partyType,
        int $partyId,
        ?int $targetCurrencyId = null,
        ?string $targetCurrencyCode = null,
        ?string $targetExchangeRate = null,
    ): array {
        $tenant = TenantModel::query()->with('baseCurrency')->find($tenantId);
        $baseCurrency = $tenant?->baseCurrency;
        $baseCurrencyId = $tenant?->base_currency_id === null ? null : (int) $tenant->base_currency_id;
        $baseCurrencyCode = $baseCurrency?->code;

        if ($targetCurrencyId === null && $targetCurrencyCode === null) {
            $targetCurrencyId = $baseCurrencyId;
            $targetCurrencyCode = $baseCurrencyCode;
            $targetExchangeRate = '1.000000';
        } elseif ($targetCurrencyId !== null && $targetCurrencyCode === null) {
            $targetCurrency = CurrencyModel::query()->find($targetCurrencyId);
            $targetCurrencyCode = $targetCurrency?->code;
        } elseif ($targetCurrencyCode !== null && $targetCurrencyId === null) {
            $targetCurrency = CurrencyModel::query()->where('code', $targetCurrencyCode)->first();
            $targetCurrencyId = $targetCurrency === null ? null : (int) $targetCurrency->getKey();
        }

        if ($targetCurrencyId !== null && $baseCurrencyId !== null && $targetCurrencyId === $baseCurrencyId) {
            $targetExchangeRate = '1.000000';
        } elseif ($targetCurrencyCode !== null && $baseCurrencyCode !== null && $targetCurrencyCode === $baseCurrencyCode) {
            $targetExchangeRate = '1.000000';
        } elseif ($targetExchangeRate !== null && $this->math->compare($targetExchangeRate, '0.000000') > 0) {
            $targetExchangeRate = $this->math->normalize($targetExchangeRate);
        } elseif ($targetCurrencyId !== null || $targetCurrencyCode !== null) {
            $latestInvoiceRate = Invoice::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($query) use ($targetCurrencyId, $targetCurrencyCode): void {
                    if ($targetCurrencyId !== null) {
                        $query->where('currency_id', $targetCurrencyId);
                    }
                    if ($targetCurrencyCode !== null) {
                        $query->orWhere('currency_code_snapshot', $targetCurrencyCode);
                    }
                })
                ->where('exchange_rate', '>', '0')
                ->latest('invoice_date')
                ->latest('id')
                ->value('exchange_rate');

            $targetExchangeRate = $latestInvoiceRate === null ? null : $this->math->normalize((string) $latestInvoiceRate);
        }

        $query = Invoice::query()
            ->join('invoice_balances', 'invoice_balances.invoice_id', '=', 'invoices.id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.party_type', $partyType)
            ->where('invoices.party_id', $partyId)
            ->where('invoice_balances.remaining_amount', '>', '0')
            ->whereNotIn('invoices.status', [
                InvoiceStatus::Draft->value,
                InvoiceStatus::Reversed->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ]);

        $organizationUnitId === null
            ? $query->whereNull('invoices.organization_unit_id')
            : $query->where('invoices.organization_unit_id', $organizationUnitId);

        $rows = $query->select([
            'invoices.id',
            'invoices.currency_id',
            'invoices.currency_code_snapshot',
            'invoices.exchange_rate',
            'invoice_balances.remaining_amount',
        ])->get();

        $totalExposure = '0.000000';
        $totalBaseExposure = '0.000000';
        $unconvertedCurrencies = [];

        foreach ($rows as $row) {
            $remaining = $this->math->normalize((string) $row->remaining_amount);
            $invRate = (string) $row->exchange_rate;
            if ($this->math->isZero($invRate) || $this->math->isNegative($invRate)) {
                $invRate = '1.000000';
            }
            $invRate = $this->math->normalize($invRate);
            $baseAmount = $this->math->mul($remaining, $invRate);
            $totalBaseExposure = $this->math->add($totalBaseExposure, $baseAmount);

            $invCode = $row->currency_code_snapshot === null ? null : (string) $row->currency_code_snapshot;
            $invCurrId = $row->currency_id === null ? null : (int) $row->currency_id;

            $isDirectTarget = ($targetCurrencyCode !== null && $invCode === $targetCurrencyCode)
                || ($targetCurrencyId !== null && $invCurrId === $targetCurrencyId);

            if ($isDirectTarget) {
                $targetAmount = $remaining;
            } elseif (($targetCurrencyId !== null && $targetCurrencyId === $baseCurrencyId)
                || ($targetCurrencyCode !== null && $baseCurrencyCode !== null && $targetCurrencyCode === $baseCurrencyCode)
                || ($targetCurrencyId === null && $targetCurrencyCode === null)) {
                $targetAmount = $baseAmount;
            } elseif ($targetExchangeRate !== null && $this->math->compare($targetExchangeRate, '0.000000') > 0) {
                $targetAmount = $this->math->div($baseAmount, $targetExchangeRate, 6);
            } else {
                $unconvertedCurrencies[] = $invCode ?? 'Unknown';
                $targetAmount = '0.000000';
            }

            $totalExposure = $this->math->add($totalExposure, $targetAmount);
        }

        return [
            'currency_code' => $targetCurrencyCode ?? $baseCurrencyCode,
            'currency_id' => $targetCurrencyId ?? $baseCurrencyId,
            'total_exposure' => $this->math->normalize($totalExposure),
            'total_base_exposure' => $this->math->normalize($totalBaseExposure),
            'unconverted_currencies' => array_values(array_unique($unconvertedCurrencies)),
        ];
    }

    public function getPayableBalancesForParty(
        int $tenantId,
        ?int $organizationUnitId,
        string $partyType,
        int $partyId,
        ?int $currencyId,
    ): array {
        $invoiceQuery = Invoice::query()
            ->join('invoice_balances', 'invoice_balances.invoice_id', '=', 'invoices.id')
            ->where('invoices.tenant_id', $tenantId)
            ->where('invoices.party_type', $partyType)
            ->where('invoices.party_id', $partyId)
            ->where('invoice_balances.remaining_amount', '>', '0')
            ->whereNotIn('invoices.status', [
                InvoiceStatus::Draft->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ])
            ->orderBy('invoices.invoice_date')
            ->orderBy('invoices.id');

        $organizationUnitId === null
            ? $invoiceQuery->whereNull('invoices.organization_unit_id')
            : $invoiceQuery->where('invoices.organization_unit_id', $organizationUnitId);
        $currencyId === null
            ? $invoiceQuery->whereNull('invoices.currency_id')
            : $invoiceQuery->where('invoices.currency_id', $currencyId);

        return $invoiceQuery
            ->pluck('invoices.id')
            ->map(fn (mixed $invoiceId): BalanceResultData => $this->getBalance((int) $invoiceId))
            ->all();
    }

    public function validatePayableState(
        int $invoiceId,
        int $tenantId,
        ?int $organizationUnitId,
        string $partyType,
        int $partyId,
        ?int $currencyId = null,
    ): BalanceResultData {
        $query = Invoice::query()
            ->with('balance')
            ->whereKey($invoiceId)
            ->where('tenant_id', $tenantId)
            ->where('party_type', $partyType)
            ->where('party_id', $partyId);

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        $currencyId === null
            ? $query->whereNull('currency_id')
            : $query->where('currency_id', $currencyId);

        $invoice = $query->firstOrFail();
        $this->statuses->assertCanSettle($invoice);

        return $this->balanceData($invoice);
    }

    private function balanceData(Invoice $invoice): BalanceResultData
    {
        $balance = $invoice->balance;

        return new BalanceResultData(
            sourceId: (int) $invoice->getKey(),
            tenantId: (int) $invoice->tenant_id,
            organizationUnitId: $invoice->organization_unit_id,
            totalAmount: (string) $balance->invoice_total,
            paidAmount: (string) $balance->paid_amount,
            creditAmount: (string) $balance->credit_allocated_amount,
            remainingAmount: (string) $balance->remaining_amount,
            status: (string) (
                $balance->status instanceof InvoiceBalanceStatus
                    ? $balance->status->value
                    : $balance->status
            ),
            partyType: $invoice->party_type,
            partyId: $invoice->party_id,
            currencyId: $invoice->currency_id,
            sourceType: 'invoice',
        );
    }
}
