<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\CreateSerialServiceInterface;
use Modules\Product\Application\DTOs\SerialData;
use Modules\Product\Domain\Entities\Serial;
use Modules\Product\Domain\RepositoryInterfaces\SerialRepositoryInterface;

class CreateSerialService extends BaseService implements CreateSerialServiceInterface
{
    public function __construct(private readonly SerialRepositoryInterface $serialRepository)
    {
        parent::__construct($serialRepository);
    }

    protected function handle(array $data): Serial
    {
        $dto = SerialData::fromArray($data);
        $entity = new Serial(
            tenantId: $dto->tenant_id,
            productId: $dto->product_id,
            serialNumber: $dto->serial_number,
            variantId: $dto->variant_id,
            batchId: $dto->batch_id,
            status: $dto->status,
            soldAt: $dto->sold_at !== null ? new \DateTimeImmutable($dto->sold_at) : null,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->serialRepository->save($entity);
    }
}
