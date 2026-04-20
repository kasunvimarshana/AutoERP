<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Purchase\Domain\Entities\PurchaseReturn;

class PurchaseReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PurchaseReturn $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'supplier_id' => $entity->getSupplierId(),
            'original_grn_id' => $entity->getOriginalGrnId(),
            'original_invoice_id' => $entity->getOriginalInvoiceId(),
            'return_number' => $entity->getReturnNumber(),
            'status' => $entity->getStatus(),
            'return_date' => $entity->getReturnDate()->format('c'),
            'return_reason' => $entity->getReturnReason(),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'subtotal' => $entity->getSubtotal(),
            'tax_total' => $entity->getTaxTotal(),
            'grand_total' => $entity->getGrandTotal(),
            'debit_note_number' => $entity->getDebitNoteNumber(),
            'journal_entry_id' => $entity->getJournalEntryId(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
