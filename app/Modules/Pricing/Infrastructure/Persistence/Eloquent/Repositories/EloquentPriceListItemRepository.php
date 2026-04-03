<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;

class EloquentPriceListItemRepository extends EloquentRepository implements PriceListItemRepositoryInterface
{
    public function __construct(PriceListItemModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PriceListItemModel $m): PriceListItem => $this->mapModelToDomainEntity($m));
    }

    public function save(PriceListItem $priceListItem): PriceListItem
    {
        $savedModel = null;
        DB::transaction(function () use ($priceListItem, &$savedModel) {
            $data = [
                'tenant_id'       => $priceListItem->getTenantId(),
                'price_list_id'   => $priceListItem->getPriceListId(),
                'product_id'      => $priceListItem->getProductId(),
                'variation_id'    => $priceListItem->getVariationId(),
                'unit_price'      => $priceListItem->getUnitPrice(),
                'min_quantity'    => $priceListItem->getMinQuantity(),
                'max_quantity'    => $priceListItem->getMaxQuantity(),
                'discount_percent'=> $priceListItem->getDiscountPercent(),
                'markup_percent'  => $priceListItem->getMarkupPercent(),
                'currency_code'   => $priceListItem->getCurrencyCode(),
                'uom_code'        => $priceListItem->getUomCode(),
                'is_active'       => $priceListItem->isActive(),
                'metadata'        => $priceListItem->getMetadata()->toArray(),
            ];
            if ($priceListItem->getId()) {
                $savedModel = $this->update($priceListItem->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof PriceListItemModel) {
            throw new \RuntimeException('Failed to save PriceListItem.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findById(int $id): ?PriceListItem
    {
        $model = $this->findModel($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByPriceList(int $priceListId): array
    {
        return $this->model
            ->where('price_list_id', $priceListId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    public function findByProduct(int $tenantId, int $productId): array
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
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

    private function mapModelToDomainEntity(PriceListItemModel $model): PriceListItem
    {
        return new PriceListItem(
            tenantId:       $model->tenant_id,
            priceListId:    $model->price_list_id,
            productId:      $model->product_id,
            unitPrice:      (float) $model->unit_price,
            minQuantity:    (float) $model->min_quantity,
            currencyCode:   $model->currency_code,
            variationId:    $model->variation_id,
            maxQuantity:    $model->max_quantity !== null ? (float) $model->max_quantity : null,
            discountPercent:(float) $model->discount_percent,
            markupPercent:  (float) $model->markup_percent,
            uomCode:        $model->uom_code,
            isActive:       (bool) $model->is_active,
            metadata:       isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:             $model->id,
            createdAt:      $model->created_at,
            updatedAt:      $model->updated_at,
        );
    }
}
