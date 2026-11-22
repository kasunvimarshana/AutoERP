<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
