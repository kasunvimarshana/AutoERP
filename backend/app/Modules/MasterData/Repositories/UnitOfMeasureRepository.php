<?php

namespace App\Modules\MasterData\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\MasterData\Models\UnitOfMeasure;

class UnitOfMeasureRepository extends BaseRepository
{
    /**
     * UnitOfMeasureRepository constructor.
     *
     * @param UnitOfMeasure $model
     */
    public function __construct(UnitOfMeasure $model)
    {
        parent::__construct($model);
    }

    /**
     * Get units by type
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByType(string $type)
    {
        return $this->model->where('type', $type)->where('is_active', true)->get();
    }

    /**
     * Get base units
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBaseUnits()
    {
        return $this->model->whereNull('base_unit_id')->where('is_active', true)->get();
    }

    /**
     * Get system units
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSystemUnits()
    {
        return $this->model->where('is_system', true)->where('is_active', true)->get();
    }

    /**
     * Find by abbreviation
     *
     * @param string $abbreviation
     * @return UnitOfMeasure|null
     */
    public function findByAbbreviation(string $abbreviation): ?UnitOfMeasure
    {
        return $this->model->where('abbreviation', $abbreviation)->first();
    }
}
