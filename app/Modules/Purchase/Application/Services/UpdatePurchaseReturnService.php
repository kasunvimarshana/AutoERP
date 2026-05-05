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
            supplierId: $dto->supplierId,
            returnNumber: $dto->returnNumber,
            returnDate: new \DateTimeImmutable($dto->returnDate),
            currencyId: $dto->currencyId,
            exchangeRate: $dto->exchangeRate,
            originalGrnId: $dto->originalGrnId,
            originalInvoiceId: $dto->originalInvoiceId,
            returnReason: $dto->returnReason,
            subtotal: $dto->subtotal,
            taxTotal: $dto->taxTotal,
            grandTotal: $dto->grandTotal,
            debitNoteNumber: $dto->debitNoteNumber,
            journalEntryId: $dto->journalEntryId,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->repo->save($entity);
    }
}
