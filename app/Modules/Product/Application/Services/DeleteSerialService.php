<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteSerialServiceInterface;
use Modules\Product\Domain\Exceptions\SerialNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\SerialRepositoryInterface;

class DeleteSerialService extends BaseService implements DeleteSerialServiceInterface
{
    public function __construct(private readonly SerialRepositoryInterface $serialRepository)
    {
        parent::__construct($serialRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->serialRepository->find($id);

        if (! $entity) {
            throw new SerialNotFoundException($id);
        }

        return $this->serialRepository->delete($id);
    }
}
