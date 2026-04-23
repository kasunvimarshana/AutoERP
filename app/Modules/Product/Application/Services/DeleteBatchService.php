<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteBatchServiceInterface;
use Modules\Product\Domain\Exceptions\BatchNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\BatchRepositoryInterface;

class DeleteBatchService extends BaseService implements DeleteBatchServiceInterface
{
    public function __construct(private readonly BatchRepositoryInterface $batchRepository)
    {
        parent::__construct($batchRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $entity = $this->batchRepository->find($id);

        if (! $entity) {
            throw new BatchNotFoundException($id);
        }

        return $this->batchRepository->delete($id);
    }
}
