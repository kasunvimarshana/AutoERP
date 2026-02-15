<?php

namespace App\Modules\MasterData\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\MasterData\Models\Currency;

class CurrencyRepository extends BaseRepository
{
    /**
     * CurrencyRepository constructor.
     *
     * @param Currency $model
     */
    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }

    /**
     * Find currency by code
     *
     * @param string $code
     * @return Currency|null
     */
    public function findByCode(string $code): ?Currency
    {
        return $this->model->where('code', $code)->first();
    }

    /**
     * Get base currency
     *
     * @return Currency|null
     */
    public function getBaseCurrency(): ?Currency
    {
        return $this->model->where('is_base_currency', true)->first();
    }

    /**
     * Get active currencies
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveCurrencies()
    {
        return $this->model->where('is_active', true)->get();
    }
}
