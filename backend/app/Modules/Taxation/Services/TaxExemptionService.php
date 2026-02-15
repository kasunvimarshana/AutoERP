<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Services;

use App\Core\Services\BaseService;
use App\Modules\Taxation\Repositories\TaxExemptionRepository;

class TaxExemptionService extends BaseService
{
    public function __construct(TaxExemptionRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getActiveExemptions()
    {
        return $this->repository->getActiveExemptions();
    }

    public function findByEntity(string $entityType, int $entityId)
    {
        return $this->repository->findByEntity($entityType, $entityId);
    }

    public function findByCustomer(int $customerId, $date = null)
    {
        return $this->repository->findByCustomer($customerId, $date);
    }

    public function findByProduct(int $productId, $date = null)
    {
        return $this->repository->findByProduct($productId, $date);
    }

    public function findValidExemptions(string $entityType, int $entityId, $date = null)
    {
        return $this->repository->findValidExemptions($entityType, $entityId, $date);
    }
}
