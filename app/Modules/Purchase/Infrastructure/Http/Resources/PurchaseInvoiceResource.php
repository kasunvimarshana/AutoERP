<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;

class PurchaseInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PurchaseInvoice $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'supplier_id' => $entity->getSupplierId(),
            'grn_header_id' => $entity->getGrnHeaderId(),
            'purchase_order_id' => $entity->getPurchaseOrderId(),
            'invoice_number' => $entity->getInvoiceNumber(),
            'supplier_invoice_number' => $entity->getSupplierInvoiceNumber(),
            'status' => $entity->getStatus(),
            'invoice_date' => $entity->getInvoiceDate()->format('c'),
            'due_date' => $entity->getDueDate()->format('c'),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'subtotal' => $entity->getSubtotal(),
            'tax_total' => $entity->getTaxTotal(),
            'discount_total' => $entity->getDiscountTotal(),
            'grand_total' => $entity->getGrandTotal(),
            'ap_account_id' => $entity->getApAccountId(),
            'journal_entry_id' => $entity->getJournalEntryId(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
