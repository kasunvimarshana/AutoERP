<?php

declare(strict_types=1);

namespace App\Modules\Taxation\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Taxation\Models\TaxGroup;
use Illuminate\Database\Eloquent\Collection;

class TaxGroupRepository extends BaseRepository
{
    public function __construct(TaxGroup $model)
    {
        parent::__construct($model);
    }

    public function getActiveTaxGroups(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->with(['taxRates'])
            ->orderBy('name')
            ->get();
    }

    public function findByCode(string $code): ?TaxGroup
    {
        return $this->model
            ->where('code', $code)
            ->with(['taxRates'])
            ->first();
    }

    public function getValidTaxGroups($date = null): Collection
    {
        $date = $date ?? now();
        
        return $this->model
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date);
            })
            ->with(['taxRates'])
            ->orderBy('name')
            ->get();
    }

    public function attachTaxRate(int $taxGroupId, int $taxRateId, array $attributes = []): bool
    {
        $taxGroup = $this->findById($taxGroupId);
        
        if (!$taxGroup) {
            return false;
        }

        $taxGroup->taxRates()->attach($taxRateId, $attributes);
        
        return true;
    }

    public function detachTaxRate(int $taxGroupId, int $taxRateId): bool
    {
        $taxGroup = $this->findById($taxGroupId);
        
        if (!$taxGroup) {
            return false;
        }

        $taxGroup->taxRates()->detach($taxRateId);
        
        return true;
    }

    public function syncTaxRates(int $taxGroupId, array $taxRates): bool
    {
        $taxGroup = $this->findById($taxGroupId);
        
        if (!$taxGroup) {
            return false;
        }

        $taxGroup->taxRates()->sync($taxRates);
        
        return true;
    }
}
