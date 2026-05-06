<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\CreatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseReturnData;
use Modules\Purchase\Domain\Entities\PurchaseReturn;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;

class CreatePurchaseReturnService extends BaseService implements CreatePurchaseReturnServiceInterface
{
    public function __construct(private readonly PurchaseReturnRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseReturn
    {
        $dto = PurchaseReturnData::fromArray($data);

        $entity = new PurchaseReturn(
            tenantId: $dto->tenant_id,
            supplierId: $dto->supplier_id,
            returnNumber: $dto->return_number,
            status: $dto->status,
            returnDate: new \DateTimeImmutable($dto->return_date),
            currencyId: $dto->currency_id,
            exchangeRate: $dto->exchange_rate,
            originalGrnId: $dto->original_grn_id,
            originalInvoiceId: $dto->original_invoice_id,
            returnReason: $dto->return_reason,
            subtotal: $dto->subtotal,
            taxTotal: $dto->tax_total,
            grandTotal: $dto->grand_total,
            debitNoteNumber: $dto->debit_note_number,
            journalEntryId: $dto->journal_entry_id,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->repo->save($entity);
    }
}
