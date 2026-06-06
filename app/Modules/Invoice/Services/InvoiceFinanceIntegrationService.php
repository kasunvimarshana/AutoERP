<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\FinancePostingRequest;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Invoice\Models\Invoice;

final class InvoiceFinanceIntegrationService
{
    public function __construct(private readonly FinancePostingInterface $financePostings) {}

    /**
     * @param  list<FinancePostingLine>  $lines
     */
    public function preparePostingRequest(int $invoiceId, string $postingDate, array $lines): FinancePostingRequest
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);

        return new FinancePostingRequest(
            source: new PostingSourceData(
                sourceType: 'invoice',
                sourceId: (int) $invoice->getKey(),
                tenantId: (int) $invoice->tenant_id,
                organizationUnitId: $invoice->organization_unit_id,
            ),
            postingDate: $postingDate,
            currencyId: $invoice->currency_id,
            exchangeRate: (string) $invoice->exchange_rate,
            lines: $lines,
            description: 'Invoice posting '.$invoice->invoice_number,
        );
    }

    public function validatePostingRequest(FinancePostingRequest $request): void
    {
        $this->financePostings->validatePosting($request);
    }
}
