<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Domain\Exceptions\PricingIntegrityException;
use Modules\Pricing\Domain\Exceptions\PricingRecordNotFoundException;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;

class PricingDomainService
{
    public function __construct(
        private readonly PriceListRepositoryInterface $priceLists,
        private readonly CustomerRepositoryInterface $customers,
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly ItemRepositoryInterface $items,
        private readonly UnitOfMeasureRepositoryInterface $uoms,
    ) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (strtolower(trim($resource))) {
            'lists', 'price-lists' => 'price_lists',
            'items', 'price-list-items' => 'price_list_items',
            'supplier-lists', 'supplier-price-lists' => 'supplier_price_lists',
            'customer-lists', 'customer-price-lists' => 'customer_price_lists',
            default => str_replace('-', '_', strtolower(trim($resource))),
        };
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
        return number_format((float) ($value ?? 0), (int) config('pricing.precision.scale', 4), '.', '');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public function normalizeEnum(string $family, mixed $value, array $allowed, mixed $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw PricingIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse((string) $value)->toDateString();
    }

    public function assertDateRange(?string $from, ?string $to): void
    {
        if ($from !== null && $to !== null && CarbonImmutable::parse($to)->lt(CarbonImmutable::parse($from))) {
            throw PricingIntegrityException::rule('Valid-to date cannot be earlier than valid-from date.');
        }
    }

    public function assertDiscount(string $type, string|int|float|null $value): void
    {
        $amount = (float) ($value ?? 0);

        if ($amount < 0) {
            throw PricingIntegrityException::rule('Discount value cannot be negative.');
        }

        if ($type === 'percentage' && $amount > 100) {
            throw PricingIntegrityException::rule('Percentage discount cannot exceed 100.');
        }
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw PricingIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function ensureMutable(string $resource, Model $record, array $definition, bool $updating = false): void
    {
        $immutable = config("pricing.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw PricingIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw PricingIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantPriceList(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $this->priceLists->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw PricingRecordNotFoundException::for('Price list', $id);
        }

        return $record;
    }

    public function assertTenantCustomer(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $this->customers->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw PricingRecordNotFoundException::for('Customer', $id);
        }

        return $record;
    }

    public function assertTenantSupplier(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $this->suppliers->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw PricingRecordNotFoundException::for('Supplier', $id);
        }

        return $record;
    }

    public function assertTenantItem(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $this->items->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw PricingRecordNotFoundException::for('Item', $id);
        }

        return $record;
    }

    public function assertTenantUom(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $this->uoms->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw PricingRecordNotFoundException::for('Unit of measure', $id);
        }

        return $record;
    }

    /**
     * @return array<string, string|float>
     */
    public function calculateNetPrice(Model $priceItem): array
    {
        $price = (float) $priceItem->price;
        $discountValue = (float) $priceItem->discount_value;
        $discountAmount = match ((string) $priceItem->discount_type) {
            'percentage' => $price * ($discountValue / 100),
            'fixed' => min($price, $discountValue),
            default => 0.0,
        };

        $net = max(0.0, $price - $discountAmount);

        return [
            'price' => $this->normalizeDecimal($price),
            'discount_amount' => $this->normalizeDecimal($discountAmount),
            'net_price' => $this->normalizeDecimal($net),
        ];
    }
}
