<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sales\Domain\Entities\SalesInvoice;

class SalesInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SalesInvoice $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'customer_id' => $entity->getCustomerId(),
            'sales_order_id' => $entity->getSalesOrderId(),
            'shipment_id' => $entity->getShipmentId(),
            'invoice_number' => $entity->getInvoiceNumber(),
            'status' => $entity->getStatus(),
            'invoice_date' => $entity->getInvoiceDate()->format('c'),
            'due_date' => $entity->getDueDate()->format('c'),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'subtotal' => $entity->getSubtotal(),
            'tax_total' => $entity->getTaxTotal(),
            'discount_total' => $entity->getDiscountTotal(),
            'grand_total' => $entity->getGrandTotal(),
            'paid_amount' => $entity->getPaidAmount(),
            'balance_due' => $entity->getBalanceDue(),
            'ar_account_id' => $entity->getArAccountId(),
            'journal_entry_id' => $entity->getJournalEntryId(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
