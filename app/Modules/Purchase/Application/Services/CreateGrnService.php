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
            tenantId: $dto->tenant_id,
            supplierId: $dto->supplier_id,
            warehouseId: $dto->warehouse_id,
            grnNumber: $dto->grn_number,
            status: $dto->status,
            receivedDate: new \DateTimeImmutable($dto->received_date),
            currencyId: $dto->currency_id,
            exchangeRate: $dto->exchange_rate,
            createdBy: $dto->created_by,
            purchaseOrderId: $dto->purchase_order_id,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->repo->save($entity);
    }
}
