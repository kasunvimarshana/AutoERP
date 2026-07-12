<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use InvalidArgumentException;
use Modules\Finance\Contracts\FinancePaymentReversalInterface;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingResultData;
use Modules\Finance\Models\FinanceJournalEntry;

final class ReversalService implements FinancePaymentReversalInterface, FinanceSourceReversalInterface
{
    public function __construct(private readonly FinancePostingInterface $postings) {}

    public function reverseJournal(
        int $journalId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData {
        return $this->postings->reverseJournal($journalId, $reversalDate, $reversedBy, $reason);
    }

    public function reverseSource(
        int $tenantId,
        ?int $organizationUnitId,
        string $sourceModule,
        string $sourceType,
        int $sourceId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData {
        $query = FinanceJournalEntry::query()
            ->where('tenant_id', $tenantId)
            ->where('source_module', $sourceModule)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 'posted');

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        $journal = $query->latest('journal_date')->latest('id')->first();
        if (! $journal instanceof FinanceJournalEntry) {
            throw new InvalidArgumentException('No posted journal exists for the requested source document.');
        }

        return $this->reverseJournal((int) $journal->getKey(), $reversalDate, $reversedBy, $reason);
    }

    public function reverseInvoice(
        int $tenantId,
        ?int $organizationUnitId,
        int $invoiceId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData {
        return $this->reverseSource($tenantId, $organizationUnitId, 'invoice', 'invoice', $invoiceId, $reversalDate, $reversedBy, $reason);
    }

    public function reversePayment(
        int $tenantId,
        ?int $organizationUnitId,
        int $paymentId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData {
        return $this->reverseSource($tenantId, $organizationUnitId, 'payment', 'payment', $paymentId, $reversalDate, $reversedBy, $reason);
    }

    public function reverseInventory(
        int $tenantId,
        ?int $organizationUnitId,
        string $sourceType,
        int $sourceId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData {
        return $this->reverseSource($tenantId, $organizationUnitId, 'inventory', $sourceType, $sourceId, $reversalDate, $reversedBy, $reason);
    }

    public function reverseTax(
        int $tenantId,
        ?int $organizationUnitId,
        string $sourceType,
        int $sourceId,
        string $reversalDate,
        ?int $reversedBy = null,
        ?string $reason = null,
    ): PostingResultData {
        return $this->reverseSource($tenantId, $organizationUnitId, 'tax', $sourceType, $sourceId, $reversalDate, $reversedBy, $reason);
    }
}
