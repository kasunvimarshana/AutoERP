<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use App\Support\Repositories\EloquentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;

class EloquentPriceListItemRepository extends EloquentRepository implements PriceListItemRepositoryInterface
{
    public function __construct(PriceListItemModel $model)
    {
        parent::__construct($model);
    }

    public function getForTenant(int|string $tenantId, array $with = []): Collection
    {
        return $this->query($with)->where('tenant_id', $tenantId)->get();
    }

    public function paginateForTenant(int|string $tenantId, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->where('tenant_id', $tenantId)->paginate($perPage);
    }

    public function findForTenantById(int|string $tenantId, int|string $id, array $with = []): ?Model
    {
        return $this->query($with)->where('tenant_id', $tenantId)->whereKey($id)->first();
    }

    public function getForOrganizationUnit(int|string $organizationUnitId, array $with = []): Collection
    {
        return $this->query($with)->where('organization_unit_id', $organizationUnitId)->get();
    }

    public function paginateForOrganizationUnit(int|string $organizationUnitId, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->query($with)->where('organization_unit_id', $organizationUnitId)->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function findBestForContext(int|string $tenantId, int|string $priceListId, int|string $itemId, int|string $uomId, string|int|float $quantity, ?string $date = null, array $context = [], array $with = []): ?Model
    {
        $query = $this->query($with)
            ->where('tenant_id', $tenantId)
            ->where('price_list_id', $priceListId)
            ->where('item_id', $itemId)
            ->where('uom_id', $uomId)
            ->where('min_quantity', '<=', $quantity);

        $this->applyValidity($query, $date);
        $this->applyDimensions($query, $context);

        foreach (config('pricing.price_dimensions', []) as $column) {
            $query->orderByRaw("CASE WHEN {$column} IS NULL THEN 0 ELSE 1 END DESC");
        }

        return $query->orderByDesc('min_quantity')->latest('id')->first();
    }

    private function applyValidity(Builder $query, ?string $date): Builder
    {
        if ($date === null) {
            return $query;
        }

        return $query
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $date);
            })
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('valid_to')->orWhere('valid_to', '>=', $date);
            });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function applyDimensions(Builder $query, array $context): Builder
    {
        foreach (config('pricing.price_dimensions', []) as $column) {
            $value = $context[$column] ?? null;

            if ($value === null || $value === '') {
                $query->whereNull($column);

                continue;
            }

            $query->where(function (Builder $query) use ($column, $value): void {
                $query->where($column, $value)->orWhereNull($column);
            });
        }

        return $query;
    }
}
