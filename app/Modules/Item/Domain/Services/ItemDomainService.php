<?php

declare(strict_types=1);

namespace Modules\Item\Domain\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Item\Application\Repositories\ItemAttributeRepositoryInterface;
use Modules\Item\Application\Repositories\ItemAttributeValueRepositoryInterface;
use Modules\Item\Application\Repositories\ItemBrandRepositoryInterface;
use Modules\Item\Application\Repositories\ItemCategoryRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Application\Repositories\ItemVariantRepositoryInterface;
use Modules\Item\Domain\Exceptions\ItemIntegrityException;
use Modules\Item\Domain\Exceptions\ItemRecordNotFoundException;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;

class ItemDomainService
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
        private readonly ItemCategoryRepositoryInterface $categories,
        private readonly ItemBrandRepositoryInterface $brands,
        private readonly ItemAttributeRepositoryInterface $attributes,
        private readonly ItemAttributeValueRepositoryInterface $attributeValues,
        private readonly ItemVariantRepositoryInterface $variants,
        private readonly UnitOfMeasureRepositoryInterface $uoms,
    ) {}

    public function normalizeResourceKey(string $resource): string
    {
        return strtolower(trim($resource));
    }

    public function normalizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function normalizeDecimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('item.precision.scale', 4), '.', '');
    }

    public function normalizeEnum(string $family, mixed $value, mixed $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $allowed = config("item.types.{$family}", []);
        $normalized = in_array(strtolower((string) $value), ['fixed', 'percentage'], true)
            ? strtolower((string) $value)
            : strtoupper((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw ItemIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw ItemIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    public function assertTenantItem(int|string $tenantId, int|string|null $id, string $label = 'Item'): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $this->items->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw ItemRecordNotFoundException::for($label, $id);
        }

        return $record;
    }

    public function assertTenantCategory(int|string $tenantId, int|string|null $id): void
    {
        if ($id === null) {
            return;
        }

        $this->assertTenantRecord($this->categories->findForTenantById($tenantId, $id), 'Item category', $id);
    }

    public function assertTenantBrand(int|string $tenantId, int|string|null $id): void
    {
        if ($id === null) {
            return;
        }

        $this->assertTenantRecord($this->brands->findForTenantById($tenantId, $id), 'Item brand', $id);
    }

    public function assertTenantAttribute(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        return $this->assertTenantRecord($this->attributes->findForTenantById($tenantId, $id), 'Item attribute', $id);
    }

    public function assertTenantAttributeValue(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        return $this->assertTenantRecord($this->attributeValues->findForTenantById($tenantId, $id), 'Item attribute value', $id);
    }

    public function assertTenantVariant(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        return $this->assertTenantRecord($this->variants->findForTenantById($tenantId, $id), 'Item variant', $id);
    }

    public function assertTenantUom(int|string $tenantId, int|string|null $id): void
    {
        if ($id === null) {
            return;
        }

        $this->assertTenantRecord($this->uoms->findForTenantById($tenantId, $id), 'Unit of measure', $id);
    }

    public function assertDifferentItems(int|string $comboItemId, int|string $componentItemId): void
    {
        if ((string) $comboItemId === (string) $componentItemId) {
            throw ItemIntegrityException::rule('A combo item cannot contain itself as a component.');
        }
    }

    public function assertMaximumStock(?string $minimumStock, ?string $maximumStock): void
    {
        if ($maximumStock !== null && $minimumStock !== null && (float) $maximumStock < (float) $minimumStock) {
            throw ItemIntegrityException::rule('Maximum stock cannot be lower than minimum stock.');
        }
    }

    private function assertTenantRecord(?Model $record, string $label, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        if ($record === null) {
            throw ItemRecordNotFoundException::for($label, $id);
        }

        return $record;
    }
}
