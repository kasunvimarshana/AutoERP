<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Application\DTOs\PricingRecordData;
use Modules\Pricing\Application\Repositories\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Application\Repositories\SupplierPriceListRepositoryInterface;
use Modules\Pricing\Domain\Exceptions\PricingIntegrityException;
use Modules\Pricing\Domain\Exceptions\PricingRecordNotFoundException;
use Modules\Pricing\Domain\Services\PricingDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class PricingService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly PriceListRepositoryInterface $priceLists,
        private readonly PriceListItemRepositoryInterface $priceListItems,
        private readonly CustomerPriceListRepositoryInterface $customerPriceLists,
        private readonly SupplierPriceListRepositoryInterface $supplierPriceLists,
        private readonly PricingDomainService $domain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("pricing.resources.{$key}");

        if (! is_array($definition)) {
            throw PricingRecordNotFoundException::for('Pricing resource', $resource);
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
            throw PricingRecordNotFoundException::for($definition['label'] ?? $resource, $id);
        }

        return $record;
    }

    public function create(string $resource, PricingRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);

        return $repository->transaction(function () use ($definition, $repository, $data): Model {
            $record = $repository->create($this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId));

            return $this->reloadRecord($definition['key'], $data->tenantId, $record->getKey());
        });
    }

    public function update(string $resource, int|string $tenantId, int|string $id, PricingRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(function () use ($definition, $repository, $record, $data, $tenantId): Model {
            $updated = $repository->update($record, [
                ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
                'row_version' => $this->domain->nextRowVersion($record),
            ]);

            return $this->reloadRecord($definition['key'], $tenantId, $updated->getKey());
        });
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, false);

        return $repository->transaction(fn (): bool => $repository->delete($record));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function resolve(int|string $tenantId, array $context): array
    {
        $this->ensureTenantExists($tenantId);

        $context = $this->normalizeResolutionContext($tenantId, $context);
        $priceListIds = $this->candidatePriceListIds($tenantId, $context);

        foreach ($priceListIds as $priceListId) {
            $item = $this->priceListItems->findBestForContext(
                tenantId: $tenantId,
                priceListId: $priceListId,
                itemId: $context['item_id'],
                uomId: $context['uom_id'],
                quantity: $context['quantity'],
                date: $context['date'],
                context: $context,
                with: ['priceList']
            );

            if ($item === null) {
                continue;
            }

            return [
                'matched' => true,
                'price_list_id' => $item->price_list_id,
                'price_list_item_id' => $item->getKey(),
                'item_id' => $item->item_id,
                'variant_id' => $item->variant_id,
                'uom_id' => $item->uom_id,
                'quantity' => $context['quantity'],
                'min_quantity' => $item->min_quantity,
                'discount_type' => $item->discount_type,
                'discount_value' => $item->discount_value,
                ...$this->domain->calculateNetPrice($item),
            ];
        }

        return [
            'matched' => false,
            'price_list_ids' => $priceListIds,
        ];
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw PricingRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw PricingIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        unset($attributes['row_version']);

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'price_lists' => $this->preparePriceListAttributes($attributes),
            'price_list_items' => $this->preparePriceListItemAttributes($attributes, $tenantId),
            'supplier_price_lists' => $this->prepareSupplierPriceListAttributes($attributes, $tenantId),
            'customer_price_lists' => $this->prepareCustomerPriceListAttributes($attributes, $tenantId),
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

        foreach (['min_quantity', 'price', 'discount_value'] as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        foreach (['valid_from', 'valid_to'] as $column) {
            if (array_key_exists($column, $attributes)) {
                $attributes[$column] = $this->domain->normalizeDate($attributes[$column]);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preparePriceListAttributes(array $attributes): array
    {
        $attributes['type'] = $this->domain->normalizeEnum('price list type', $attributes['type'] ?? null, config('pricing.price_list_types', []), config('pricing.defaults.price_list_type'));
        $attributes['is_active'] = $attributes['is_active'] ?? config('pricing.defaults.is_active', true);
        $attributes['is_default'] = $attributes['is_default'] ?? config('pricing.defaults.is_default', false);
        $this->domain->assertDateRange($attributes['valid_from'] ?? null, $attributes['valid_to'] ?? null);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function preparePriceListItemAttributes(array $attributes, int|string $tenantId): array
    {
        $priceList = $this->domain->assertTenantPriceList($tenantId, $attributes['price_list_id'] ?? null);
        $this->domain->assertTenantItem($tenantId, $attributes['item_id'] ?? null);
        $this->domain->assertTenantUom($tenantId, $attributes['uom_id'] ?? null);

        $attributes['organization_unit_id'] = $attributes['organization_unit_id'] ?? $priceList?->organization_unit_id;
        $attributes['discount_type'] = $this->domain->normalizeEnum('discount type', $attributes['discount_type'] ?? null, config('pricing.discount_types', []), config('pricing.defaults.discount_type'));
        $attributes['discount_value'] = $attributes['discount_value'] ?? $this->domain->normalizeDecimal(0);
        $attributes['min_quantity'] = $attributes['min_quantity'] ?? $this->domain->normalizeDecimal(config('pricing.defaults.minimum_quantity', 1));

        $this->domain->assertDiscount($attributes['discount_type'], $attributes['discount_value']);
        $this->domain->assertDateRange($attributes['valid_from'] ?? null, $attributes['valid_to'] ?? null);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareSupplierPriceListAttributes(array $attributes, int|string $tenantId): array
    {
        $priceList = $this->domain->assertTenantPriceList($tenantId, $attributes['price_list_id'] ?? null);
        $this->domain->assertTenantSupplier($tenantId, $attributes['supplier_id'] ?? null);
        $attributes['organization_unit_id'] = $attributes['organization_unit_id'] ?? $priceList?->organization_unit_id;
        $attributes['priority'] = $attributes['priority'] ?? config('pricing.defaults.priority', 0);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareCustomerPriceListAttributes(array $attributes, int|string $tenantId): array
    {
        $priceList = $this->domain->assertTenantPriceList($tenantId, $attributes['price_list_id'] ?? null);
        $this->domain->assertTenantCustomer($tenantId, $attributes['customer_id'] ?? null);
        $attributes['organization_unit_id'] = $attributes['organization_unit_id'] ?? $priceList?->organization_unit_id;
        $attributes['priority'] = $attributes['priority'] ?? config('pricing.defaults.priority', 0);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function normalizeResolutionContext(int|string $tenantId, array $context): array
    {
        $context['type'] = $this->domain->normalizeEnum('price list type', $context['type'] ?? null, config('pricing.price_list_types', []), config('pricing.defaults.price_list_type'));
        $context['quantity'] = $this->domain->normalizeDecimal($context['quantity'] ?? config('pricing.defaults.minimum_quantity', 1));
        $context['date'] = $this->domain->normalizeDate($context['date'] ?? now()->toDateString());

        $this->domain->assertTenantItem($tenantId, $context['item_id'] ?? null);
        $this->domain->assertTenantUom($tenantId, $context['uom_id'] ?? null);
        $this->domain->assertTenantCustomer($tenantId, $context['customer_id'] ?? null);
        $this->domain->assertTenantSupplier($tenantId, $context['supplier_id'] ?? null);

        if (($context['price_list_id'] ?? null) !== null) {
            $this->domain->assertTenantPriceList($tenantId, $context['price_list_id']);
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, int|string>
     */
    private function candidatePriceListIds(int|string $tenantId, array $context): array
    {
        if (($context['price_list_id'] ?? null) !== null) {
            return [$context['price_list_id']];
        }

        $ids = [];

        if (($context['customer_id'] ?? null) !== null) {
            foreach ($this->customerPriceLists->getForCustomer($tenantId, $context['customer_id']) as $assignment) {
                $ids[] = $assignment->price_list_id;
            }
        }

        if (($context['supplier_id'] ?? null) !== null) {
            foreach ($this->supplierPriceLists->getForSupplier($tenantId, $context['supplier_id']) as $assignment) {
                $ids[] = $assignment->price_list_id;
            }
        }

        $default = $this->priceLists->findDefaultForTenantByType($tenantId, $context['type'], $context['date']);

        if ($default !== null) {
            $ids[] = $default->getKey();
        }

        foreach ($this->priceLists->getActiveForTenantByType($tenantId, $context['type'], $context['date']) as $priceList) {
            $ids[] = $priceList->getKey();
        }

        return array_values(array_unique($ids));
    }

    private function reloadRecord(string $resource, int|string $tenantId, int|string $id): Model
    {
        return $this->find($resource, $tenantId, $id);
    }
}
