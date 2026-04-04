<?php
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;

class EloquentPriceListItemRepository extends EloquentRepository implements PriceListItemRepositoryInterface
{
    public function __construct(PriceListItemModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?PriceListItem
    {
        $m = parent::findById($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByPriceList(int $priceListId): array
    {
        return $this->model->where('price_list_id', $priceListId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function findByProduct(int $priceListId, int $productId): ?PriceListItem
    {
        $m = $this->model
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function create(array $data): PriceListItem
    {
        return $this->toEntity(parent::create($data));
    }

    public function update(PriceListItem $item, array $data): PriceListItem
    {
        $m = $this->model->findOrFail($item->id);
        return $this->toEntity(parent::update($m, $data));
    }

    public function delete(PriceListItem $item): bool
    {
        return parent::delete($this->model->findOrFail($item->id));
    }

    private function toEntity(object $m): PriceListItem
    {
        return new PriceListItem(
            id: $m->id,
            priceListId: $m->price_list_id,
            productId: $m->product_id,
            price: (float) $m->price,
            variantId: $m->variant_id ?? null,
            minQty: isset($m->min_qty) ? (float) $m->min_qty : null,
            maxQty: isset($m->max_qty) ? (float) $m->max_qty : null,
            discountPercent: isset($m->discount_percent) ? (float) $m->discount_percent : null,
            uom: $m->uom,
        );
    }
}
