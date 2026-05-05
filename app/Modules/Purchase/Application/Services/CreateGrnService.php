<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\CreateGrnServiceInterface;
use Modules\Purchase\Application\DTOs\GrnHeaderData;
use Modules\Purchase\Domain\Entities\GrnHeader;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnHeaderRepositoryInterface;

class CreateGrnService extends BaseService implements CreateGrnServiceInterface
{
    public function __construct(private readonly GrnHeaderRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): GrnHeader
    {
        $dto = GrnHeaderData::fromArray($data);

        $entity = new GrnHeader(
            tenantId: $dto->tenantId,
            supplierId: $dto->supplierId,
            warehouseId: $dto->warehouseId,
            grnNumber: $dto->grnNumber,
            status: $dto->status,
            receivedDate: new \DateTimeImmutable($dto->receivedDate),
            currencyId: $dto->currencyId,
            exchangeRate: $dto->exchangeRate,
            createdBy: $dto->createdBy,
            purchaseOrderId: $dto->purchaseOrderId,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->repo->save($entity);
    }
}
