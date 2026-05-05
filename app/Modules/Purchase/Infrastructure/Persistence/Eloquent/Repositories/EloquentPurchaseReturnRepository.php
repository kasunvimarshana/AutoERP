<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Purchase\Domain\Entities\PurchaseReturn;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;

class EloquentPurchaseReturnRepository extends EloquentRepository implements PurchaseReturnRepositoryInterface
{
    public function __construct(PurchaseReturnModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PurchaseReturnModel $m): PurchaseReturn => $this->mapToDomain($m));
    }

    public function save(PurchaseReturn $entity): PurchaseReturn
    {
        $data = [
            'tenant_id' => $entity->getTenantId(),
            'supplier_id' => $entity->getSupplierId(),
            'original_grn_id' => $entity->getOriginalGrnId(),
            'original_invoice_id' => $entity->getOriginalInvoiceId(),
            'return_number' => $entity->getReturnNumber(),
            'status' => $entity->getStatus(),
            'return_date' => $entity->getReturnDate()->format('Y-m-d'),
            'return_reason' => $entity->getReturnReason(),
            'currency_id' => $entity->getCurrencyId(),
            'exchange_rate' => $entity->getExchangeRate(),
            'subtotal' => $entity->getSubtotal(),
            'tax_total' => $entity->getTaxTotal(),
            'grand_total' => $entity->getGrandTotal(),
                        'discount_total' => $entity->getDiscountTotal(),
            'debit_note_number' => $entity->getDebitNoteNumber(),
            'journal_entry_id' => $entity->getJournalEntryId(),
            'notes' => $entity->getNotes(),
            'metadata' => $entity->getMetadata(),
        ];

        if ($entity->getId()) {
            $model = $this->update($entity->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        return $this->toDomainEntity($model);
    }

    public function find(int|string $id, array $columns = ['*']): ?PurchaseReturn
    {
        return parent::find($id, $columns);
    }

    private function mapToDomain(PurchaseReturnModel $m): PurchaseReturn
    {
        return new PurchaseReturn(
            tenantId: (int) $m->tenant_id,
            supplierId: (int) $m->supplier_id,
            returnNumber: (string) $m->return_number,
            status: (string) $m->status,
            returnDate: $m->return_date instanceof \DateTimeInterface ? $m->return_date : new \DateTimeImmutable((string) $m->return_date),
            currencyId: (int) $m->currency_id,
            exchangeRate: (string) $m->exchange_rate,
            originalGrnId: $m->original_grn_id !== null ? (int) $m->original_grn_id : null,
            originalInvoiceId: $m->original_invoice_id !== null ? (int) $m->original_invoice_id : null,
            returnReason: $m->return_reason !== null ? (string) $m->return_reason : null,
            subtotal: (string) $m->subtotal,
            taxTotal: (string) $m->tax_total,
            grandTotal: (string) $m->grand_total,
                        discountTotal: isset($m->discount_total) ? (string) $m->discount_total : '0',
            debitNoteNumber: $m->debit_note_number !== null ? (string) $m->debit_note_number : null,
            journalEntryId: $m->journal_entry_id !== null ? (int) $m->journal_entry_id : null,
            notes: $m->notes !== null ? (string) $m->notes : null,
            metadata: $m->metadata,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
