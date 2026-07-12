<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\Tax\InvoiceTaxDocumentMapper;
use Modules\Tax\Services\TaxDocumentIntegrationService;

final class InvoiceStatusService
{
    public function __construct(
        private readonly TaxDocumentIntegrationService $taxDocuments,
        private readonly InvoiceSourceRestorationService $sourceRestoration,
        private readonly InvoiceTaxDocumentMapper $taxDocumentMapper,
        private readonly InvoicePostingPlanService $postingPlans,
    ) {}

    /** @return array<string, list<string>> */
    private function transitions(): array
    {
        return [
            InvoiceStatus::Draft->value => [
                InvoiceStatus::Approved->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::Approved->value => [
                InvoiceStatus::Posted->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::Posted->value => [
                InvoiceStatus::PartiallyPaid->value,
                InvoiceStatus::Paid->value,
            ],
            InvoiceStatus::PartiallyPaid->value => [InvoiceStatus::Paid->value],
            InvoiceStatus::Paid->value => [],
            InvoiceStatus::Reversed->value => [],
            InvoiceStatus::Cancelled->value => [],
            InvoiceStatus::Void->value => [],
        ];
    }

    /** @return list<string> */
    public function settlementStatuses(): array
    {
        return [
            InvoiceStatus::Posted->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::Paid->value,
        ];
    }

    public function assertCanTransition(InvoiceStatus|string $from, InvoiceStatus|string $to): void
    {
        $fromValue = $from instanceof InvoiceStatus ? $from->value : $from;
        $toValue = $to instanceof InvoiceStatus ? $to->value : $to;
        if (! in_array($toValue, $this->transitions()[$fromValue] ?? [], true)) {
            throw new InvalidArgumentException(sprintf('Invoice status cannot transition from %s to %s.', $fromValue, $toValue));
        }
    }

    public function transition(
        Invoice $invoice,
        InvoiceStatus $to,
        ?int $actorId = null,
        ?string $reason = null,
    ): Invoice {
        return $this->transitionLocked($invoice, $to, null, $actorId, $reason);
    }

    public function transitionIfVersion(
        Invoice $invoice,
        InvoiceStatus $to,
        int $expectedVersion,
        ?int $actorId = null,
        ?string $reason = null,
    ): Invoice {
        if ($expectedVersion < 1) {
            throw new InvalidArgumentException('Expected invoice version must be positive.');
        }

        return $this->transitionLocked($invoice, $to, $expectedVersion, $actorId, $reason);
    }

    private function transitionLocked(
        Invoice $invoice,
        InvoiceStatus $to,
        ?int $expectedVersion,
        ?int $actorId,
        ?string $reason,
    ): Invoice {
        return DB::transaction(function () use ($invoice, $to, $expectedVersion, $actorId, $reason): Invoice {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->getKey());
            if ($expectedVersion !== null && $expectedVersion !== (int) $invoice->row_version) {
                throw new InvalidArgumentException(
                    'Invoice was changed by another request. Reload it before performing this action.',
                );
            }

            $from = $invoice->status instanceof InvoiceStatus
                ? $invoice->status
                : InvoiceStatus::from((string) $invoice->status);
            $this->assertCanTransition($from, $to);

            $updates = ['status' => $to->value];
            if ($to === InvoiceStatus::Approved) {
                $updates['approved_by'] = $actorId;
                $updates['approved_at'] = now();
            }
            if ($to === InvoiceStatus::Posted) {
                $updates['posted_by'] = $actorId;
                $updates['posted_at'] = now();
            }
            if (in_array($to, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                $updates['cancelled_by'] = $actorId;
                $updates['cancelled_at'] = now();
                $updates['cancellation_reason'] = $this->nullableReason($reason);
            }

            $invoice->forceFill($updates)->save();
            if (in_array($to, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                $this->sourceRestoration->restore($invoice, $to);
            }
            if ($to === InvoiceStatus::Posted) {
                $postedInvoice = $invoice->refresh();
                $this->taxDocuments->post($this->taxDocumentMapper->map($postedInvoice));
                $this->postingPlans->post($postedInvoice, $actorId);
            }

            return $invoice->refresh();
        });
    }

    public function assertEditable(Invoice $invoice): void
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::from((string) $invoice->status);
        if ($status !== InvoiceStatus::Draft) {
            throw new InvalidArgumentException('Only draft invoices can be edited directly.');
        }
    }

    public function assertCanSettle(Invoice $invoice): void
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::from((string) $invoice->status);
        if (! in_array($status->value, $this->settlementStatuses(), true)) {
            throw new InvalidArgumentException('Only posted invoices can be settled.');
        }
    }

    private function nullableReason(?string $reason): ?string
    {
        if ($reason === null) {
            return null;
        }
        $reason = trim($reason);

        return $reason === '' ? null : $reason;
    }
}
