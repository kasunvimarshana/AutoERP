<?php

declare(strict_types=1);

namespace Modules\Item\Application\Services;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Item\Application\DTOs\ItemRecordData;
use Modules\Item\Domain\Exceptions\ItemIntegrityException;
use Modules\Item\Domain\Exceptions\ItemRecordNotFoundException;
use Modules\Item\Domain\Services\ItemDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class ItemService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly ItemDomainService $domain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("item.resources.{$key}");

        if (! is_array($definition)) {
            throw ItemRecordNotFoundException::for('Item resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);
        $repository = $this->repository($resource);
        $criteria = ['tenant_id' => $tenantId, ...$filters];

        return $perPage === null
            ? $repository->getWhere($criteria)
            : $repository->paginateWhere($criteria, $perPage);
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw ItemRecordNotFoundException::for($definition['label'] ?? $resource, $id);
        }

        return $record;
    }

    public function create(string $resource, ItemRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);
        $attributes = $this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId);

        return $repository->transaction(fn (): Model => $repository->create($attributes));
    }

    public function update(string $resource, int|string $tenantId, int|string $id, ItemRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->assertRowVersion($data->rowVersion, $record);
        $attributes = [
            ...$this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId),
            'row_version' => $this->domain->nextRowVersion($record),
        ];

        return $repository->transaction(fn (): Model => $repository->update($record, $attributes));
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $repository = $this->repository($resource);

        return $repository->transaction(fn (): bool => $repository->delete($this->find($resource, $tenantId, $id)));
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw ItemRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw ItemIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int $tenantId): array
    {
        unset($attributes['row_version']);

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'categories', 'brands' => $this->prepareTreeAttributes($resource, $attributes, $tenantId),
            'items' => $this->prepareItemAttributes($attributes, $tenantId),
            'attributes' => $this->prepareAttributeAttributes($attributes, $tenantId),
            'attribute-values' => $this->prepareAttributeValueAttributes($attributes, $tenantId),
            'variants' => $this->prepareVariantAttributes($attributes, $tenantId),
            'variant-attributes' => $this->prepareVariantAttributeAttributes($attributes, $tenantId),
            'variant-attribute-values' => $this->prepareVariantAttributeValueAttributes($attributes, $tenantId),
            'combo-items' => $this->prepareComboItemAttributes($attributes, $tenantId),
            'identifiers' => $this->prepareIdentifierAttributes($attributes, $tenantId),
            default => $attributes,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeScalars(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $attributes[$key] = $this->domain->normalizeText($value);
            }
        }

        foreach ([
            'standard_cost', 'cost_price', 'sales_price', 'estimated_service_time_hours',
            'incentive_value', 'minimum_stock', 'maximum_stock', 'reorder_point',
            'reorder_quantity', 'safety_stock', 'quantity',
        ] as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareTreeAttributes(string $resource, array $attributes, int $tenantId): array
    {
        if (($attributes['parent_id'] ?? null) !== null) {
            $parentResource = $resource === 'categories' ? 'categories' : 'brands';
            $this->find($parentResource, $tenantId, $attributes['parent_id']);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareItemAttributes(array $attributes, int $tenantId): array
    {
        $attributes['type'] = $this->domain->normalizeEnum('item', $attributes['type'] ?? null, config('item.types.item.0', 'PHYSICAL'));
        $attributes['status'] = $this->domain->normalizeEnum('status', $attributes['status'] ?? null, config('item.types.status.0', 'DRAFT'));
        $attributes['incentive_type'] = $this->domain->normalizeEnum('incentive', $attributes['incentive_type'] ?? null, config('item.types.incentive.0', 'fixed'));

        $this->domain->assertTenantCategory($tenantId, $attributes['category_id'] ?? null);
        $this->domain->assertTenantBrand($tenantId, $attributes['brand_id'] ?? null);
        $this->domain->assertTenantUom($tenantId, $attributes['base_uom_id'] ?? null);
        $this->domain->assertTenantUom($tenantId, $attributes['purchase_uom_id'] ?? null);
        $this->domain->assertTenantUom($tenantId, $attributes['sales_uom_id'] ?? null);
        $this->domain->assertMaximumStock($attributes['minimum_stock'] ?? null, $attributes['maximum_stock'] ?? null);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributeAttributes(array $attributes, int $tenantId): array
    {
        $attributes['type'] = $this->domain->normalizeEnum('attribute', $attributes['type'] ?? null, config('item.types.attribute.1', 'SELECT'));

        if (($attributes['group_id'] ?? null) !== null) {
            $this->find('attribute-groups', $tenantId, $attributes['group_id']);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributeValueAttributes(array $attributes, int $tenantId): array
    {
        $this->domain->assertTenantAttribute($tenantId, $attributes['attribute_id'] ?? null);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareVariantAttributes(array $attributes, int $tenantId): array
    {
        $this->domain->assertTenantItem($tenantId, $attributes['item_id'] ?? null);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareVariantAttributeAttributes(array $attributes, int $tenantId): array
    {
        $this->domain->assertTenantItem($tenantId, $attributes['item_id'] ?? null);
        $this->domain->assertTenantAttribute($tenantId, $attributes['attribute_id'] ?? null);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareVariantAttributeValueAttributes(array $attributes, int $tenantId): array
    {
        $variant = $this->domain->assertTenantVariant($tenantId, $attributes['variant_id'] ?? null);
        $attributeValue = $this->domain->assertTenantAttributeValue($tenantId, $attributes['attribute_value_id'] ?? null);

        if ($variant !== null && $attributeValue !== null && (int) $variant->tenant_id !== (int) $attributeValue->tenant_id) {
            throw ItemIntegrityException::rule('Variant attribute value must belong to the same tenant as the variant.');
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareComboItemAttributes(array $attributes, int $tenantId): array
    {
        $this->domain->assertTenantItem($tenantId, $attributes['combo_item_id'] ?? null, 'Combo item');
        $this->domain->assertTenantItem($tenantId, $attributes['component_item_id'] ?? null, 'Component item');
        $this->domain->assertDifferentItems($attributes['combo_item_id'], $attributes['component_item_id']);
        $this->domain->assertTenantVariant($tenantId, $attributes['component_variant_id'] ?? null);
        $this->domain->assertTenantUom($tenantId, $attributes['uom_id'] ?? null);
        $attributes['incentive_type'] = $this->domain->normalizeEnum('incentive', $attributes['incentive_type'] ?? null, config('item.types.incentive.0', 'fixed'));

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareIdentifierAttributes(array $attributes, int $tenantId): array
    {
        $this->domain->assertTenantItem($tenantId, $attributes['item_id'] ?? null);
        $this->domain->assertTenantVariant($tenantId, $attributes['variant_id'] ?? null);
        $attributes['technology'] = $this->domain->normalizeEnum('identifier_technology', $attributes['technology'] ?? null, config('item.types.identifier_technology.0', 'barcode_1d'));

        return $attributes;
    }
}

