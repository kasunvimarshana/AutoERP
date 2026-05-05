<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\CustomerPriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\SupplierPriceListModel;

class EloquentPriceListItemRepository extends EloquentRepository implements PriceListItemRepositoryInterface
{
    private string $priceListItemsTable;
    private string $priceListsTable;
    private string $customerPriceListsTable;
    private string $supplierPriceListsTable;

    public function __construct(
        PriceListItemModel $model,
        PriceListModel $priceListModel,
        CustomerPriceListModel $customerPriceListModel,
        SupplierPriceListModel $supplierPriceListModel,
    ) {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PriceListItemModel $model): PriceListItem => $this->mapModelToDomainEntity($model));
        $this->priceListItemsTable = $model->getTable();
        $this->priceListsTable = $priceListModel->getTable();
        $this->customerPriceListsTable = $customerPriceListModel->getTable();
        $this->supplierPriceListsTable = $supplierPriceListModel->getTable();
    }

    public function save(PriceListItem $priceListItem): PriceListItem
    {
        $data = [
            'tenant_id' => $priceListItem->getTenantId(),
            'price_list_id' => $priceListItem->getPriceListId(),
            'product_id' => $priceListItem->getProductId(),
            'variant_id' => $priceListItem->getVariantId(),
            'uom_id' => $priceListItem->getUomId(),
            'min_quantity' => $priceListItem->getMinQuantity(),
            'price' => $priceListItem->getPrice(),
            'discount_pct' => $priceListItem->getDiscountPct(),
            'valid_from' => $priceListItem->getValidFrom()?->format('Y-m-d'),
            'valid_to' => $priceListItem->getValidTo()?->format('Y-m-d'),
            'row_version' => $priceListItem->getRowVersion(),
        ];

        if ($priceListItem->getId()) {
            $model = $this->update($priceListItem->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var PriceListItemModel $model */

        return $this->toDomainEntity($model);
    }

    public function findBestMatch(
        int $tenantId,
        string $type,
        int $productId,
        ?int $variantId,
        int $uomId,
        string $quantity,
        int $currencyId,
        ?int $customerId,
        ?int $supplierId,
        \DateTimeInterface $priceDate,
    ): ?array {
        $date = $priceDate->format('Y-m-d');

        $pli = $this->priceListItemsTable . ' as pli';
        $pl = $this->priceListsTable . ' as pl';
        $cpl = $this->customerPriceListsTable . ' as cpl';
        $spl = $this->supplierPriceListsTable . ' as spl';

        $query = DB::table($pli)
            ->join($pl, 'pl.id', '=', 'pli.price_list_id')
            ->leftJoin($cpl, function ($join) use ($tenantId, $customerId): void {
                $join->on('cpl.price_list_id', '=', 'pl.id')
                    ->where('cpl.tenant_id', '=', $tenantId);

                if ($customerId !== null) {
                    $join->where('cpl.customer_id', '=', $customerId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->leftJoin($spl, function ($join) use ($tenantId, $supplierId): void {
                $join->on('spl.price_list_id', '=', 'pl.id')
                    ->where('spl.tenant_id', '=', $tenantId);

                if ($supplierId !== null) {
                    $join->where('spl.supplier_id', '=', $supplierId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->where('pli.tenant_id', $tenantId)
            ->where('pl.tenant_id', $tenantId)
            ->where('pl.type', $type)
            ->where('pl.currency_id', $currencyId)
            ->where('pl.is_active', true)
            ->where(function ($q) use ($date): void {
                $q->whereNull('pl.valid_from')
                    ->orWhereDate('pl.valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date): void {
                $q->whereNull('pl.valid_to')
                    ->orWhereDate('pl.valid_to', '>=', $date);
            })
            ->where('pli.product_id', $productId)
            ->where('pli.uom_id', $uomId)
            ->whereRaw('CAST(pli.min_quantity AS DECIMAL(20,6)) <= CAST(? AS DECIMAL(20,6))', [$quantity])
            ->where(function ($q) use ($date): void {
                $q->whereNull('pli.valid_from')
                    ->orWhereDate('pli.valid_from', '<=', $date);
            })
            ->where(function ($q) use ($date): void {
                $q->whereNull('pli.valid_to')
                    ->orWhereDate('pli.valid_to', '>=', $date);
            });

        if ($variantId !== null) {
            $query->where(function ($q) use ($variantId): void {
                $q->where('pli.variant_id', $variantId)
                    ->orWhereNull('pli.variant_id');
            });
        } else {
            $query->whereNull('pli.variant_id');
        }

        if ($type === 'sales') {
            if ($customerId !== null) {
                $query->where(function ($q): void {
                    $q->whereNotNull('cpl.id')
                        ->orWhere('pl.is_default', true);
                });
            } else {
                $query->where('pl.is_default', true);
            }
        }

        if ($type === 'purchase') {
            if ($supplierId !== null) {
                $query->where(function ($q): void {
                    $q->whereNotNull('spl.id')
                        ->orWhere('pl.is_default', true);
                });
            } else {
                $query->where('pl.is_default', true);
            }
        }

        $result = $query
            ->select([
                'pli.id',
                'pli.price_list_id',
                'pli.price',
                'pli.discount_pct',
                'pli.min_quantity',
                'pli.variant_id',
                DB::raw('COALESCE(cpl.priority, spl.priority, 0) as assignment_priority'),
                DB::raw('CASE WHEN cpl.id IS NOT NULL OR spl.id IS NOT NULL THEN 1 ELSE 0 END as assigned_match'),
                DB::raw('CASE WHEN pli.variant_id IS NULL THEN 0 ELSE 1 END as variant_specificity'),
            ])
            ->orderByDesc('assigned_match')
            ->orderByDesc('assignment_priority')
            ->orderByDesc('variant_specificity')
            ->orderByDesc('min_quantity')
            ->first();

        return $result !== null ? (array) $result : null;
    }

    public function find(int|string $id, array $columns = ['*']): ?PriceListItem
    {
        return parent::find($id, $columns);
    }

    private function mapModelToDomainEntity(PriceListItemModel $model): PriceListItem
    {
        return new PriceListItem(
            id: (int) $model->id,
            tenantId: (int) $model->tenant_id,
            priceListId: (int) $model->price_list_id,
            productId: (int) $model->product_id,
            variantId: $model->variant_id !== null ? (int) $model->variant_id : null,
            uomId: (int) $model->uom_id,
            minQuantity: number_format((float) $model->min_quantity, 6, '.', ''),
            price: number_format((float) $model->price, 6, '.', ''),
            discountPct: number_format((float) $model->discount_pct, 6, '.', ''),
            validFrom: $model->valid_from,
            validTo: $model->valid_to,
            rowVersion: (int) ($model->row_version ?? 1),
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
