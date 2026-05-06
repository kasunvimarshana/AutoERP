<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Purchase\Application\Contracts\UpdateGrnServiceInterface;
use Modules\Purchase\Application\DTOs\GrnHeaderData;
use Modules\Purchase\Domain\Entities\GrnHeader;
use Modules\Purchase\Domain\Exceptions\GrnNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnHeaderRepositoryInterface;

class UpdateGrnService extends BaseService implements UpdateGrnServiceInterface
{
    public function __construct(private readonly GrnHeaderRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function handle(array $data): GrnHeader
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->repo->find($id);

        if (! $entity) {
            throw new GrnNotFoundException($id);
        }

        $dto = GrnHeaderData::fromArray($data);

        $entity->update(
            supplierId: $dto->supplier_id,
            warehouseId: $dto->warehouse_id,
            grnNumber: $dto->grn_number,
            receivedDate: new \DateTimeImmutable($dto->received_date),
            currencyId: $dto->currency_id,
            exchangeRate: $dto->exchange_rate,
            purchaseOrderId: $dto->purchase_order_id,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->repo->save($entity);
    }
}
