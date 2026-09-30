<?php

declare(strict_types=1);

namespace Modules\Invoice\Contracts;

use Modules\Invoice\DTOs\BalanceResultData;
use Modules\Invoice\DTOs\InvoiceBalanceResult;

interface InvoiceBalanceProviderInterface
{
    /**
     * @param  list<int>  $invoiceIds
     * @return array<int, array{
     *     id: int,
     *     invoice_number: string|null,
     *     invoice_date: string|null,
     *     currency_code: string|null,
     *     name: string
     * }>
     */
    public function getInvoiceReferences(array $invoiceIds): array;

    public function getInvoiceBalance(int $invoiceId): InvoiceBalanceResult;

    public function getBalance(int $invoiceId): BalanceResultData;

    public function getInvoiceStatus(int $invoiceId): string;

    /**
     * @param  list<int>  $partyIds
     * @return array<int, list<array{amount: string, currency_code: string|null}>>
     */
    public function getOutstandingTotalsForParties(
        int $tenantId,
        ?int $organizationUnitId,
        string $partyType,
        array $partyIds,
    ): array;

    /**
     * @return list<BalanceResultData>
     */
    public function getPayableBalancesForParty(
        int $tenantId,
        ?int $organizationUnitId,
        string $partyType,
        int $partyId,
    ): array;

    public function validatePayableState(
        int $invoiceId,
        int $tenantId,
        ?int $organizationUnitId,
        string $partyType,
        int $partyId,
        ?int $currencyId = null,
    ): BalanceResultData;
}
