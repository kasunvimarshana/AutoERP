<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;

final class InvoiceIssuanceService
{
    public function __construct(private readonly InvoiceStatusService $statuses) {}

    public function advance(
        Invoice $invoice,
        InvoiceStatus $targetStatus,
        ?int $actorId = null,
    ): Invoice {
        return match ($targetStatus) {
            InvoiceStatus::Draft => $invoice->refresh(),
            InvoiceStatus::Approved => $this->statuses->transition(
                $invoice,
                InvoiceStatus::Approved,
                $actorId,
            ),
            InvoiceStatus::Posted => $this->post($invoice, $actorId),
            default => throw new InvalidArgumentException(
                'New invoices may only be created as draft, approved, or posted through the governed issuance workflow.',
            ),
        };
    }

    private function post(Invoice $invoice, ?int $actorId): Invoice
    {
        $approved = $this->statuses->transition(
            $invoice,
            InvoiceStatus::Approved,
            $actorId,
        );

        return $this->statuses->transition(
            $approved,
            InvoiceStatus::Posted,
            $actorId,
        );
    }
}
