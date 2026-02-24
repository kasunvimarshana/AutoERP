<?php
namespace Modules\Sales\Infrastructure\Repositories;

use Modules\Sales\Domain\Contracts\PriceListRepositoryInterface;
use Modules\Sales\Infrastructure\Models\PriceListItemModel;
use Modules\Sales\Infrastructure\Models\PriceListModel;

class PriceListRepository implements PriceListRepositoryInterface
{
    public function findById(string $id): ?object
    {
        return PriceListModel::with('items')->find($id);
    }

    public function paginate(array $filters, int $perPage = 15): object
    {
        $query = PriceListModel::query();
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (!empty($filters['currency_code'])) {
            $query->where('currency_code', strtoupper($filters['currency_code']));
        }
        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): object
    {
        return PriceListModel::create($data);
    }

    public function update(string $id, array $data): object
    {
        $priceList = PriceListModel::findOrFail($id);
        $priceList->update($data);
        return $priceList->fresh('items');
    }

    public function delete(string $id): bool
    {
        return PriceListModel::findOrFail($id)->delete();
    }

    public function addItem(array $data): object
    {
        return PriceListItemModel::create($data);
    }

    /**
     * Find the best-matching price list item for the given product, variant, and quantity.
     *
     * Returns the item with the highest min_qty that is still <= $qty.
     * Variant-scoped items take precedence over product-scoped items.
     */
    public function findItem(string $priceListId, string $productId, ?string $variantId, string $qty): ?object
    {
        $query = PriceListItemModel::where('price_list_id', $priceListId)
            ->where('product_id', $productId)
            ->whereRaw('min_qty <= ?', [$qty])
            ->orderBy('min_qty', 'desc');

        if ($variantId !== null) {
            // Prefer variant-scoped rule; fall back to product-scoped.
            $item = (clone $query)->where('variant_id', $variantId)->first();
            if ($item) {
                return $item;
            }
        }

        return $query->whereNull('variant_id')->first();
    }

    public function itemsForPriceList(string $priceListId): array
    {
        return PriceListItemModel::where('price_list_id', $priceListId)->get()->all();
    }
}
