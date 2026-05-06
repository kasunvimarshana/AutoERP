<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Finance\Domain\Entities\CreditMemo;
use Modules\Finance\Domain\RepositoryInterfaces\CreditMemoRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\CreditMemoModel;

class EloquentCreditMemoRepository extends EloquentRepository implements CreditMemoRepositoryInterface
{
    public function __construct(CreditMemoModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (CreditMemoModel $m): CreditMemo => $this->mapToDomain($m));
    }

    public function save(CreditMemo $cm): CreditMemo
    {
        $data = [
            'tenant_id' => $cm->getTenantId(),
            'party_id' => $cm->getPartyId(),
            'party_type' => $cm->getPartyType(),
            'return_order_id' => $cm->getReturnOrderId(),
            'return_order_type' => $cm->getReturnOrderType(),
            'credit_memo_number' => $cm->getCreditMemoNumber(),
            'amount' => $cm->getAmount(),
            'status' => $cm->getStatus(),
            'issued_date' => $cm->getIssuedDate()->format('Y-m-d'),
            'applied_to_invoice_id' => $cm->getAppliedToInvoiceId(),
            'applied_to_invoice_type' => $cm->getAppliedToInvoiceType(),
            'notes' => $cm->getNotes(),
            'journal_entry_id' => $cm->getJournalEntryId(),
        ];

        $model = $cm->getId() ? $this->update($cm->getId(), $data) : $this->create($data);

        /** @var CreditMemoModel $model */
        return $this->toDomainEntity($model);
    }

    private function mapToDomain(CreditMemoModel $m): CreditMemo
    {
        return new CreditMemo(
            tenantId: (int) $m->tenant_id,
            partyId: (int) $m->party_id,
            partyType: (string) $m->party_type,
            creditMemoNumber: (string) $m->credit_memo_number,
            amount: (float) $m->amount,
            issuedDate: $m->issued_date,
            status: (string) $m->status,
            returnOrderId: $m->return_order_id !== null ? (int) $m->return_order_id : null,
            returnOrderType: $m->return_order_type,
            appliedToInvoiceId: $m->applied_to_invoice_id !== null ? (int) $m->applied_to_invoice_id : null,
            appliedToInvoiceType: $m->applied_to_invoice_type,
            notes: $m->notes,
            journalEntryId: $m->journal_entry_id !== null ? (int) $m->journal_entry_id : null,
            id: (int) $m->id,
            createdAt: $m->created_at,
            updatedAt: $m->updated_at,
        );
    }
}
