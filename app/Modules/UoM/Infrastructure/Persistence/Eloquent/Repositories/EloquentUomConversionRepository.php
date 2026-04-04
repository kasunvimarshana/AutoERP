<?php
namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;

class EloquentUomConversionRepository extends EloquentRepository implements UomConversionRepositoryInterface
{
    public function __construct(UomConversionModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?UomConversion
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByFromTo(int $fromId, int $toId, ?int $productId = null): ?UomConversion
    {
        $query = $this->model->where('from_uom_id', $fromId)->where('to_uom_id', $toId);
        if ($productId !== null) {
            $query->where('product_id', $productId);
        } else {
            $query->whereNull('product_id');
        }
        $model = $query->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function create(array $data): UomConversion
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(UomConversion $conversion, array $data): UomConversion
    {
        $model = $this->model->findOrFail($conversion->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function delete(UomConversion $conversion): bool
    {
        $model = $this->model->findOrFail($conversion->id);
        return parent::delete($model);
    }

    private function toEntity(UomConversionModel $model): UomConversion
    {
        return new UomConversion(
            id: $model->id,
            fromUomId: $model->from_uom_id,
            toUomId: $model->to_uom_id,
            factor: (float) $model->factor,
            productId: $model->product_id,
        );
    }
}
