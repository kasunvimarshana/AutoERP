<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateBatchServiceInterface;
use Modules\Product\Application\DTOs\BatchData;
use Modules\Product\Domain\Entities\Batch;
use Modules\Product\Domain\Exceptions\BatchNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\BatchRepositoryInterface;

class UpdateBatchService extends BaseService implements UpdateBatchServiceInterface
{
    public function __construct(private readonly BatchRepositoryInterface $batchRepository)
    {
        parent::__construct($batchRepository);
    }

    protected function handle(array $data): Batch
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->batchRepository->find($id);

        if (! $entity) {
            throw new BatchNotFoundException($id);
        }

        $dto = BatchData::fromArray($data);
        $entity->update(
            batchNumber: $dto->batch_number,
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
