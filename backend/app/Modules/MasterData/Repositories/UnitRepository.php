<?php

namespace App\Modules\MasterData\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\MasterData\Models\Unit;

class UnitRepository extends BaseRepository
{
    public function __construct(Unit $model)
    {
        $this->model = $model;
    }

    /**
     * Get all active units.
     */
    public function getActive()
    {
        return $this->model->active()->get();
    }

    /**
     * Get units by type.
     */
    public function getByType(string $type)
    {
        return $this->model->active()->ofType($type)->get();
    }

    /**
     * Find unit by code.
     */
    public function findByCode(string $code)
    {
        return $this->model->where('code', $code)->first();
    }
}
