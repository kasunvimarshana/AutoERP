<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Constants\InvoiceFinanceSource;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Models\InvoiceBalance;
use Modules\Invoice\Services\Tax\InvoiceTaxDocumentMapper;
use Modules\Tax\Services\TaxDocumentIntegrationService;

final class InvoiceReversalService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InvoicePostingPlanService $postingPlans,
        private readonly TaxDocumentIntegrationService $taxDocuments,
        private readonly InvoiceTaxDocumentMapper $taxDocumentMapper,
        private readonly InvoiceSourceRestorationService $sourceRestoration,
        private readonly InvoiceBalanceService $balances,
    ) {}

    public function reverse(
        Invoice $invoice,
        int $expectedVersion,
        string $reversalDate,
        string $reason,
        ?int $actorId = null,
    ): Invoice {
        if ($expectedVersion < 1) {
            throw new InvalidArgumentException('Expected invoice version must be positive.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Invoice reversal reason is required.');
        }
        $this->assertDate($reversalDate);

        return DB::transaction(function () use ($invoice, $expectedVersion, $reversalDate, $reason, $actorId): Invoice {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            if ((int) $invoice->row_version !== $expectedVersion) {
                throw new InvalidArgumentException(
                    'Invoice was changed by another request. Reload it before performing this action.',
                );
            }
            $status = $invoice->status instanceof InvoiceStatus
                ? $invoice->status
                : InvoiceStatus::from((string) $invoice->status);
            if ($status !== InvoiceStatus::Posted) {
                throw new InvalidArgumentException('Only an unsettled posted invoice can be reversed.');
            }
            $postingDate = $invoice->posted_at?->toDateString() ?? $invoice->invoice_date->toDateString();
            if ($reversalDate < $postingDate) {
                throw new InvalidArgumentException('Invoice reversal date cannot be before the posting date.');
            }

            $balance = InvoiceBalance::query()
                ->where('tenant_id', $invoice->tenant_id)
                ->where('invoice_id', $invoice->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $this->assertUnsettled($balance);

            $taxDocument = $this->taxDocumentMapper->map($invoice, $reversalDate);
            $this->taxDocuments->reverse(
                $taxDocument,
                InvoiceFinanceSource::REVERSAL_TYPE,
                InvoiceFinanceSource::REVERSAL_LINE_TYPE,
            );
            $this->postingPlans->reverse($invoice, $reversalDate, $actorId, $reason);
            $this->sourceRestoration->restore($invoice, InvoiceStatus::Reversed);

            $invoice->forceFill(['status' => InvoiceStatus::Reversed->value])->save();
            $this->balances->reverse($invoice);

            return $invoice->refresh()->load([
                'lines',
                'sources',
                'sourceLines',
                'adjustments',
                'adjustmentAllocations',
                'balance',
                'postingPlan',
            ]);
        });
    }

    private function assertUnsettled(InvoiceBalance $balance): void
    {
        foreach ([
            'paid_amount',
            'credit_allocated_amount',
            'debit_allocated_amount',
            'refunded_amount',
        ] as $field) {
            if (! $this->math->isZero((string) $balance->{$field})) {
                throw new InvalidArgumentException(
                    'Invoice settlements must be reversed before the invoice can be reversed.',
                );
            }
        }
        if ($this->math->compare((string) $balance->remaining_amount, (string) $balance->invoice_total) !== 0) {
            throw new InvalidArgumentException(
                'Invoice balance must be fully unsettled before the invoice can be reversed.',
            );
        }
    }

    private function assertDate(string $date): void
    {
        try {
            $normalized = CarbonImmutable::parse($date)->toDateString();
        } catch (\Throwable) {
            throw new InvalidArgumentException('Invoice reversal date is invalid.');
        }
        if ($normalized !== $date) {
            throw new InvalidArgumentException('Invoice reversal date must use YYYY-MM-DD format.');
        }
    }
}
