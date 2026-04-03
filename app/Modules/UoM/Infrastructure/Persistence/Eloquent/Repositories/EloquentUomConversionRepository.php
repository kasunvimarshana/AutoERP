<?php

declare(strict_types=1);

namespace Modules\UoM\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Infrastructure\Persistence\Eloquent\Models\UomConversionModel;

class EloquentUomConversionRepository extends EloquentRepository implements UomConversionRepositoryInterface
{
    public function __construct(UomConversionModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (UomConversionModel $model): UomConversion => $this->mapModelToDomainEntity($model));
    }

    public function save(UomConversion $conversion): UomConversion
    {
        $savedModel = null;

        DB::transaction(function () use ($conversion, &$savedModel) {
            if ($conversion->getId()) {
                $data = [
                    'tenant_id'   => $conversion->getTenantId(),
                    'from_uom_id' => $conversion->getFromUomId(),
                    'to_uom_id'   => $conversion->getToUomId(),
                    'factor'      => $conversion->getFactor(),
                    'is_active'   => $conversion->isActive(),
                ];
                $savedModel = $this->update($conversion->getId(), $data);
            } else {
                $savedModel = $this->model->create([
                    'tenant_id'   => $conversion->getTenantId(),
                    'from_uom_id' => $conversion->getFromUomId(),
                    'to_uom_id'   => $conversion->getToUomId(),
                    'factor'      => $conversion->getFactor(),
                    'is_active'   => $conversion->isActive(),
                ]);
            }
        });

        if (! $savedModel instanceof UomConversionModel) {
            throw new \RuntimeException('Failed to save UomConversion.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findConversion(int $tenantId, int $fromUomId, int $toUomId): ?UomConversion
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('from_uom_id', $fromUomId)
            ->where('to_uom_id', $toUomId)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    private function mapModelToDomainEntity(UomConversionModel $model): UomConversion
    {
        return new UomConversion(
            tenantId:  $model->tenant_id,
            fromUomId: $model->from_uom_id,
            toUomId:   $model->to_uom_id,
            factor:    (float) $model->factor,
            isActive:  (bool) $model->is_active,
            id:        $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }
}
