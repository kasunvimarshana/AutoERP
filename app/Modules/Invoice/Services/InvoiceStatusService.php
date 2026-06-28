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
    ) {}

    /**
     * @return array<string, list<string>>
     */
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
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::PartiallyPaid->value => [
                InvoiceStatus::Paid->value,
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::Paid->value => [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
            ],
            InvoiceStatus::Cancelled->value => [],
            InvoiceStatus::Void->value => [],
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

    public function transition(Invoice $invoice, InvoiceStatus $to): Invoice
    {
        return DB::transaction(function () use ($invoice, $to): Invoice {
            $invoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->getKey());
            $from = $invoice->status instanceof InvoiceStatus
                ? $invoice->status
                : InvoiceStatus::from((string) $invoice->status);

            $this->assertCanTransition($from, $to);

            $updates = ['status' => $to->value];
            if ($to === InvoiceStatus::Approved) {
                $updates['approved_at'] = now();
            }
            if ($to === InvoiceStatus::Posted) {
                $this->taxDocuments->snapshot($this->taxDocumentMapper->map($invoice));
                $updates['posted_at'] = now();
            }

            $invoice->forceFill($updates)->save();

            if (in_array($to, [InvoiceStatus::Cancelled, InvoiceStatus::Void], true)) {
                $this->sourceRestoration->restore($invoice);
            }
            if ($to === InvoiceStatus::Posted) {
                $this->taxDocuments->post($this->taxDocumentMapper->map($invoice->refresh()));
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

        if (! in_array($status, [
            InvoiceStatus::Posted,
            InvoiceStatus::PartiallyPaid,
            InvoiceStatus::Paid,
        ], true)) {
            throw new InvalidArgumentException('Only posted invoices can be settled.');
        }
    }
}
