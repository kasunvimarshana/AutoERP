<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Taxation\Models\TaxJurisdiction;
use Illuminate\Database\Eloquent\Collection;

class TaxJurisdictionRepository extends BaseRepository
{
    public function __construct(TaxJurisdiction $model)
    {
        parent::__construct($model);
    }

    public function getActiveJurisdictions(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->with(['taxRate', 'taxGroup'])
            ->orderByDesc('priority')
            ->get();
    }

    public function findByCode(string $code): ?TaxJurisdiction
    {
        return $this->model
            ->where('code', $code)
            ->with(['taxRate', 'taxGroup'])
            ->first();
    }

    public function findByLocation(
        string $countryCode = null,
        string $stateCode = null,
        string $cityName = null,
        string $postalCode = null
    ): ?TaxJurisdiction {
        $query = $this->model->where('is_active', true);

        if ($countryCode) {
            $query->where('country_code', $countryCode);
        }

        if ($stateCode) {
            $query->where('state_code', $stateCode);
        }

        if ($cityName) {
            $query->where('city_name', $cityName);
        }

        if ($postalCode) {
            $query->where('postal_code', $postalCode);
        }

        return $query->with(['taxRate', 'taxGroup'])
            ->orderByDesc('priority')
            ->first();
    }

    public function getMatchingJurisdictions(
        string $countryCode = null,
        string $stateCode = null,
        string $cityName = null,
        string $postalCode = null
    ): Collection {
        return $this->model
            ->where('is_active', true)
            ->forLocation($countryCode, $stateCode, $cityName, $postalCode)
            ->with(['taxRate', 'taxGroup'])
            ->get();
    }

    public function getReverseChargeJurisdictions(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->where('is_reverse_charge', true)
            ->with(['taxRate', 'taxGroup'])
            ->orderByDesc('priority')
            ->get();
    }
}
