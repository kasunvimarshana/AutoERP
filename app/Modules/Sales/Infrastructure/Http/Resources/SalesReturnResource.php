<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Domain\Entities\SalesReturn;

class SalesReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SalesReturn $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'customer_id' => $entity->getCustomerId(),
            'original_sales_order_id' => $entity->getOriginalSalesOrderId(),
            'original_invoice_id' => $entity->getOriginalInvoiceId(),
            'return_number' => $entity->getReturnNumber(),
            'status' => $entity->getStatus(),
            'return_date' => $entity->getReturnDate()->format('c'),
            'return_reason' => $entity->getReturnReason(),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'subtotal' => $entity->getSubtotal(),
            'tax_total' => $entity->getTaxTotal(),
            'restocking_fee_total' => $entity->getRestockingFeeTotal(),
            'grand_total' => $entity->getGrandTotal(),
            'credit_memo_number' => $entity->getCreditMemoNumber(),
            'journal_entry_id' => $entity->getJournalEntryId(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
