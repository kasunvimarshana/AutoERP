<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\UpdateSerialServiceInterface;
use Modules\Product\Application\DTOs\SerialData;
use Modules\Product\Domain\Entities\Serial;
use Modules\Product\Domain\Exceptions\SerialNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\SerialRepositoryInterface;

class UpdateSerialService extends BaseService implements UpdateSerialServiceInterface
{
    public function __construct(private readonly SerialRepositoryInterface $serialRepository)
    {
        parent::__construct($serialRepository);
    }

    protected function handle(array $data): Serial
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->serialRepository->find($id);

        if (! $entity) {
            throw new SerialNotFoundException($id);
        }

        $dto = SerialData::fromArray($data);
        $entity->update(
            status: $dto->status,
            soldAt: $dto->sold_at !== null ? new \DateTimeImmutable($dto->sold_at) : null,
            notes: $dto->notes,
            metadata: $dto->metadata,
        );

        return $this->serialRepository->save($entity);
    }
}
