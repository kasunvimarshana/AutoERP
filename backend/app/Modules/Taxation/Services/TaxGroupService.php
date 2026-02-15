<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Services;

use App\Core\Services\BaseService;
use App\Modules\Taxation\Repositories\TaxGroupRepository;

class TaxGroupService extends BaseService
{
    public function __construct(TaxGroupRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getActiveTaxGroups()
    {
        return $this->repository->getActiveTaxGroups();
    }

    public function findByCode(string $code)
    {
        return $this->repository->findByCode($code);
    }

    public function getValidTaxGroups($date = null)
    {
        return $this->repository->getValidTaxGroups($date);
    }

    public function attachTaxRate(int $taxGroupId, int $taxRateId, array $attributes = []): bool
    {
        return $this->repository->attachTaxRate($taxGroupId, $taxRateId, $attributes);
    }

    public function detachTaxRate(int $taxGroupId, int $taxRateId): bool
    {
        return $this->repository->detachTaxRate($taxGroupId, $taxRateId);
    }

    public function syncTaxRates(int $taxGroupId, array $taxRates): bool
    {
        return $this->repository->syncTaxRates($taxGroupId, $taxRates);
    }
}
