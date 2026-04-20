<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\UpdatePurchaseReturnServiceInterface;
use Modules\Purchase\Application\DTOs\PurchaseReturnData;
use Modules\Purchase\Domain\Entities\PurchaseReturn;
use Modules\Purchase\Domain\Exceptions\PurchaseReturnNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;

class UpdatePurchaseReturnService extends BaseService implements UpdatePurchaseReturnServiceInterface
{
    public function __construct(private readonly PurchaseReturnRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): PurchaseReturn
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new PurchaseReturnNotFoundException($id);
        }

        $dto = PurchaseReturnData::fromArray($data);

        $entity->update(
            supplierId: $dto->supplier_id,
            returnNumber: $dto->return_number,
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
