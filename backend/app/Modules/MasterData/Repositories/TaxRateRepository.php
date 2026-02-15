<?php

namespace App\Modules\MasterData\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\MasterData\Models\TaxRate;

class TaxRateRepository extends BaseRepository
{
    /**
     * TaxRateRepository constructor.
     *
     * @param TaxRate $model
     */
    public function __construct(TaxRate $model)
    {
        parent::__construct($model);
    }

    /**
     * Find by code
     *
     * @param string $code
     * @return TaxRate|null
     */
    public function findByCode(string $code): ?TaxRate
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get by type
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByType(string $type)
    {
        return $this->model->where('type', $type)->where('is_active', true)->get();
    }

    /**
     * Get valid tax rates for date
     *
     * @param \DateTime|null $date
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getValidRates($date = null)
    {
        $date = $date ?? now();

        return $this->model
            ->where('is_active', true)
            ->where(function ($query) use ($date) {
                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $date);
            })
            ->where(function ($query) use ($date) {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', $date);
            })
            ->get();
    }
}
