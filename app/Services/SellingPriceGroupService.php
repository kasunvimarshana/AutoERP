<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\SellingPriceGroup;
use App\Models\SellingPriceGroupPrice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SellingPriceGroupService
{
    public function __construct(
        private readonly AuditService $auditService
    ) {}

    public function paginate(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SellingPriceGroup::where('tenant_id', $tenantId);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): SellingPriceGroup
    {
        return DB::transaction(function () use ($data) {
            $group = SellingPriceGroup::create([
                'tenant_id' => $data['tenant_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($data['prices'] ?? [] as $price) {
                SellingPriceGroupPrice::create([
                    'selling_price_group_id' => $group->id,
                    'product_id' => $price['product_id'],
                    'product_variant_id' => $price['product_variant_id'] ?? null,
                    'price' => $price['price'],
                    'currency' => $price['currency'] ?? 'USD',
                ]);
            }

            $this->auditService->log(
                action: AuditAction::Created,
                auditableType: SellingPriceGroup::class,
                auditableId: $group->id,
                newValues: $data
            );

            return $group->fresh(['prices']);
        });
    }

    public function update(string $id, array $data): SellingPriceGroup
    {
        return DB::transaction(function () use ($id, $data) {
            $group = SellingPriceGroup::findOrFail($id);
            $oldValues = $group->toArray();

            $group->update(array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ], fn ($v) => $v !== null));

            $this->auditService->log(
                action: AuditAction::Updated,
                auditableType: SellingPriceGroup::class,
                auditableId: $group->id,
                oldValues: $oldValues,
                newValues: $data
            );

            return $group->fresh(['prices']);
        });
    }

    public function upsertPrice(string $groupId, array $priceData): SellingPriceGroupPrice
    {
        return SellingPriceGroupPrice::updateOrCreate(
            [
                'selling_price_group_id' => $groupId,
                'product_id' => $priceData['product_id'],
                'product_variant_id' => $priceData['product_variant_id'] ?? null,
            ],
            [
                'price' => $priceData['price'],
                'currency' => $priceData['currency'] ?? 'USD',
            ]
        );
    }

    public function delete(string $id): void
    {
        $group = SellingPriceGroup::findOrFail($id);

        $this->auditService->log(
            action: AuditAction::Deleted,
            auditableType: SellingPriceGroup::class,
            auditableId: $group->id,
            oldValues: $group->toArray()
        );

        $group->prices()->delete();
        $group->delete();
    }

    /**
     * Resolve the selling price for a product/variant in a given price group.
     * Falls back to the product's base_price if no group price is set.
     */
    public function resolvePrice(string $groupId, string $productId, ?string $variantId = null): ?string
    {
        $price = SellingPriceGroupPrice::where('selling_price_group_id', $groupId)
            ->where('product_id', $productId)
            ->where(function ($q) use ($variantId) {
                if ($variantId !== null) {
                    $q->where('product_variant_id', $variantId)
                        ->orWhereNull('product_variant_id');
                } else {
                    $q->whereNull('product_variant_id');
                }
            })
            ->orderByDesc('product_variant_id') // prefer variant-specific price
            ->first();

        return $price?->price;
    }
}
