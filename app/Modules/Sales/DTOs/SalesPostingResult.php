<?php

declare(strict_types=1);

namespace Modules\Sales\DTOs;

final readonly class SalesPostingResult
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
        public ?int $creditNoteId = null,
    ) {}
}
