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
            tenantId: $dto->tenantId,
            supplierId: $dto->supplierId,
            returnNumber: $dto->returnNumber,
            status: $dto->status,
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
