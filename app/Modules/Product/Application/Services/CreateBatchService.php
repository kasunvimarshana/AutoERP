<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateBatchServiceInterface;
use Modules\Product\Application\DTOs\BatchData;
use Modules\Product\Domain\Entities\Batch;
use Modules\Product\Domain\RepositoryInterfaces\BatchRepositoryInterface;

class CreateBatchService extends BaseService implements CreateBatchServiceInterface
{
    public function __construct(private readonly BatchRepositoryInterface $batchRepository)
    {
        parent::__construct($batchRepository);
    }

    protected function handle(array $data): Batch
    {
        $dto = BatchData::fromArray($data);
        $entity = new Batch(
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
            batchNumber: $dto->batch_number,
            variantId: $dto->variant_id,
            lotNumber: $dto->lot_number,
            manufacturedDate: $dto->manufactured_date !== null ? new \DateTimeImmutable($dto->manufactured_date) : null,
            expiryDate: $dto->expiry_date !== null ? new \DateTimeImmutable($dto->expiry_date) : null,
            quantity: $dto->quantity,
            status: $dto->status,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->batchRepository->save($entity);
    }
}
