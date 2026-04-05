<?php
declare(strict_types=1);
namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;

class EloquentPriceListItemRepository implements PriceListItemRepositoryInterface
{
    public function __construct(private readonly PriceListItemModel $model) {}

    private function toEntity(PriceListItemModel $m): PriceListItem
    {
        return new PriceListItem(
            $m->id, $m->tenant_id, $m->price_list_id,
            $m->product_id, $m->variant_id,
            $m->price_type, (float)$m->value, (float)$m->min_quantity,
            $m->currency ?? 'USD',
            $m->created_at, $m->updated_at,
        );
    }

    public function findById(int $id): ?PriceListItem
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByPriceList(int $tenantId, int $priceListId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('price_list_id', $priceListId)
            ->orderBy('min_quantity')
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function findForProduct(int $tenantId, int $priceListId, int $productId, ?int $variantId = null): array
    {
        $q = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->orderBy('min_quantity');

        if ($variantId !== null) {
            $q->where(fn($sub) => $sub->whereNull('variant_id')->orWhere('variant_id', $variantId));
        } else {
            $q->whereNull('variant_id');
        }

        return $q->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): PriceListItem
    {
        $m = $this->model->newQuery()->create($data);
        return $this->findById($m->id);
    }

    public function update(int $id, array $data): ?PriceListItem
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
