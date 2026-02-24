<?php

namespace Modules\Manufacturing\Infrastructure\Repositories;

use Illuminate\Support\Collection;
use Modules\Manufacturing\Domain\Contracts\BomLineRepositoryInterface;
use Modules\Manufacturing\Infrastructure\Models\BomLineModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class BomLineRepository extends BaseEloquentRepository implements BomLineRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new BomLineModel());
    }

    public function findByBom(string $bomId): Collection
    {
        return BomLineModel::where('bom_id', $bomId)->get();
    }
}
