<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;

class EloquentPriceListRepository extends EloquentRepository implements PriceListRepositoryInterface
{
    public function __construct(PriceListModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PriceListModel $m): PriceList => $this->mapModelToDomainEntity($m));
    }

    public function save(PriceList $priceList): PriceList
    {
        $savedModel = null;
        DB::transaction(function () use ($priceList, &$savedModel) {
            $data = [
                'tenant_id'      => $priceList->getTenantId(),
                'name'           => $priceList->getName(),
                'code'           => $priceList->getCode(),
                'type'           => $priceList->getType(),
                'pricing_method' => $priceList->getPricingMethod(),
                'currency_code'  => $priceList->getCurrencyCode(),
                'start_date'     => $priceList->getStartDate()?->format('Y-m-d'),
                'end_date'       => $priceList->getEndDate()?->format('Y-m-d'),
                'is_active'      => $priceList->isActive(),
                'description'    => $priceList->getDescription(),
                'metadata'       => $priceList->getMetadata()->toArray(),
            ];
            if ($priceList->getId()) {
                $savedModel = $this->update($priceList->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof PriceListModel) {
            throw new \RuntimeException('Failed to save PriceList.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findById(int $id): ?PriceList
    {
        $model = $this->findModel($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByCode(int $tenantId, string $code): ?PriceList
    {
        $model = $this->model
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByTenantAndType(int $tenantId, string $type): array
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('type', $type)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    public function list(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function mapModelToDomainEntity(PriceListModel $model): PriceList
    {
        return new PriceList(
            tenantId:      $model->tenant_id,
            name:          $model->name,
            code:          $model->code,
            type:          $model->type,
            pricingMethod: $model->pricing_method,
            currencyCode:  $model->currency_code,
            startDate:     $model->start_date ? new \DateTimeImmutable($model->start_date->format('Y-m-d')) : null,
            endDate:       $model->end_date ? new \DateTimeImmutable($model->end_date->format('Y-m-d')) : null,
            isActive:      (bool) $model->is_active,
            description:   $model->description,
            metadata:      isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:            $model->id,
            createdAt:     $model->created_at,
            updatedAt:     $model->updated_at,
        );
    }
}
