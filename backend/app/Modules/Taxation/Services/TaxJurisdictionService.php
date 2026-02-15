<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Services;

use App\Core\Services\BaseService;
use App\Modules\Taxation\Repositories\TaxJurisdictionRepository;

class TaxJurisdictionService extends BaseService
{
    public function __construct(TaxJurisdictionRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getActiveJurisdictions()
    {
        return $this->repository->getActiveJurisdictions();
    }

    public function findByCode(string $code)
    {
        return $this->repository->findByCode($code);
    }

    public function findByLocation(
        string $countryCode = null,
        string $stateCode = null,
        string $cityName = null,
        string $postalCode = null
    ) {
        return $this->repository->findByLocation($countryCode, $stateCode, $cityName, $postalCode);
    }

    public function getMatchingJurisdictions(
        string $countryCode = null,
        string $stateCode = null,
        string $cityName = null,
        string $postalCode = null
    ) {
        return $this->repository->getMatchingJurisdictions($countryCode, $stateCode, $cityName, $postalCode);
    }

    public function getReverseChargeJurisdictions()
    {
        return $this->repository->getReverseChargeJurisdictions();
    }
}
