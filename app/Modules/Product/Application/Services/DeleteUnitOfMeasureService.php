<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteUnitOfMeasureServiceInterface;
use Modules\Product\Domain\Exceptions\UnitOfMeasureNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class DeleteUnitOfMeasureService extends BaseService implements DeleteUnitOfMeasureServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $unitOfMeasureRepository)
    {
        parent::__construct($unitOfMeasureRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $unitOfMeasure = $this->unitOfMeasureRepository->find($id);

        if (! $unitOfMeasure) {
            throw new UnitOfMeasureNotFoundException($id);
        }

        return $this->unitOfMeasureRepository->delete($id);
    }
}
