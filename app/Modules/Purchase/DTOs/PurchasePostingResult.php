<?php

declare(strict_types=1);

namespace Modules\Purchase\DTOs;

final readonly class PurchasePostingResult
{
    /**
     * @param  list<int>  $inventoryMovementIds
     */
    public function __construct(
        public int $documentId,
        public string $documentNumber,
        public string $status,
        public array $inventoryMovementIds = [],
        public ?int $invoiceId = null,
        public ?int $debitNoteId = null,
    ) {}
}
