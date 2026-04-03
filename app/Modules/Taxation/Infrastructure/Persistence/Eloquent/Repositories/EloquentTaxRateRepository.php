<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Taxation\Domain\Entities\TaxRate;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Taxation\Infrastructure\Persistence\Eloquent\Models\TaxRateModel;

class EloquentTaxRateRepository extends EloquentRepository implements TaxRateRepositoryInterface
{
    public function __construct(TaxRateModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (TaxRateModel $m): TaxRate => $this->mapModelToDomainEntity($m));
    }

    public function save(TaxRate $taxRate): TaxRate
    {
        $savedModel = null;

        DB::transaction(function () use ($taxRate, &$savedModel) {
            $data = [
                'tenant_id'          => $taxRate->getTenantId(),
                'name'               => $taxRate->getName(),
                'code'               => $taxRate->getCode(),
                'tax_type'           => $taxRate->getTaxType(),
                'calculation_method' => $taxRate->getCalculationMethod(),
                'rate'               => $taxRate->getRate(),
                'jurisdiction'       => $taxRate->getJurisdiction(),
                'is_active'          => $taxRate->isActive(),
                'description'        => $taxRate->getDescription(),
                'effective_from'     => $taxRate->getEffectiveFrom()?->format('Y-m-d'),
                'effective_to'       => $taxRate->getEffectiveTo()?->format('Y-m-d'),
                'metadata'           => $taxRate->getMetadata()->toArray(),
            ];

            if ($taxRate->getId()) {
                $savedModel = $this->update($taxRate->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (!$savedModel instanceof TaxRateModel) {
            throw new \RuntimeException('Failed to save TaxRate.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByCode(int $tenantId, string $code): ?TaxRate
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('code', $code)->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByJurisdiction(int $tenantId, string $jurisdiction): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('jurisdiction', $jurisdiction)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByType(int $tenantId, string $taxType): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('tax_type', $taxType)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(TaxRateModel $model): TaxRate
    {
        return new TaxRate(
            tenantId: $model->tenant_id,
            name: $model->name,
            code: $model->code,
            taxType: $model->tax_type,
            rate: (float) $model->rate,
            calculationMethod: $model->calculation_method,
            jurisdiction: $model->jurisdiction,
            isActive: (bool) $model->is_active,
            description: $model->description,
            effectiveFrom: $model->effective_from ? new \DateTimeImmutable($model->effective_from) : null,
            effectiveTo: $model->effective_to ? new \DateTimeImmutable($model->effective_to) : null,
            metadata: isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id: $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
