<?php

declare(strict_types=1);

namespace Modules\Supplier\Validators;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemVariant;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Supplier\DTOs\CreateSupplierData;
use Modules\Supplier\DTOs\SupplierItemMappingData;
use Modules\Supplier\DTOs\UpdateSupplierData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierCategory;
use Modules\UOM\Models\UnitOfMeasureModel;

final class SupplierValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function validateCreate(CreateSupplierData $data): void
    {
        $this->assertText($data->code, 'Supplier code is required.');
        $this->assertText($data->name, 'Supplier name is required.');
        $this->assertCodeUnique($data->tenantId, $data->code);
        if ($data->supplierNumber !== null) {
            $this->assertNumberUnique($data->tenantId, $data->supplierNumber);
        }
        $this->assertNonNegative($data->creditLimit, 'Supplier credit limit cannot be negative.');
        $this->assertOrganizationScope($data->tenantId, $data->organizationUnitId);
        $this->assertCurrencyActive($data->defaultCurrencyId);
        $this->assertTaxNumbers($data->taxRegistrationNumber, $data->vatNumber, $data->svatNumber, $data->businessRegistrationNumber);
        if ($data->creditProfile !== null) {
            $this->assertNonNegative($data->creditProfile->creditLimit, 'Supplier credit profile limit cannot be negative.');
        }
    }

    public function validateUpdate(Supplier $supplier, UpdateSupplierData $data): void
    {
        if ($data->code !== null) {
            $this->assertText($data->code, 'Supplier code is required.');
            $this->assertCodeUnique((int) $supplier->tenant_id, $data->code, (int) $supplier->getKey());
        }
        if ($data->name !== null) {
            $this->assertText($data->name, 'Supplier name is required.');
        }
        if ($data->creditLimit !== null) {
            $this->assertNonNegative($data->creditLimit, 'Supplier credit limit cannot be negative.');
        }

        $this->assertOrganizationScope((int) $supplier->tenant_id, $data->organizationUnitId);
        $this->assertCurrencyActive($data->defaultCurrencyId);
        $this->assertTaxNumbers($data->taxRegistrationNumber, $data->vatNumber, $data->svatNumber, $data->businessRegistrationNumber);
        if ($data->creditProfile !== null) {
            $this->assertNonNegative($data->creditProfile->creditLimit, 'Supplier credit profile limit cannot be negative.');
        }
    }

    public function assertSupplierScope(Supplier $supplier, int $tenantId, ?int $organizationUnitId): void
    {
        $this->assertScope(
            $tenantId,
            $organizationUnitId,
            (int) $supplier->tenant_id,
            $supplier->organization_unit_id,
        );
    }

    public function assertCategoryUsable(Supplier $supplier, int $categoryId): SupplierCategory
    {
        $category = SupplierCategory::query()->findOrFail($categoryId);
        $this->assertScope(
            (int) $supplier->tenant_id,
            $supplier->organization_unit_id,
            (int) $category->tenant_id,
            $category->organization_unit_id,
        );
        if (! (bool) $category->is_active) {
            throw new InvalidArgumentException('Inactive supplier category cannot be assigned.');
        }

        return $category;
    }

    public function validateItemMapping(Supplier $supplier, SupplierItemMappingData $data): void
    {
        $this->assertNonNegative($data->minimumOrderQuantity, 'Supplier item minimum order quantity cannot be negative.');
        if ($data->leadTimeDays !== null && $data->leadTimeDays < 0) {
            throw new InvalidArgumentException('Supplier item lead time cannot be negative.');
        }

        $item = Item::query()->findOrFail($data->itemId);
        $this->assertScope(
            (int) $supplier->tenant_id,
            $supplier->organization_unit_id,
            (int) $item->tenant_id,
            $item->organization_unit_id,
        );
        if (! (bool) $item->is_active) {
            throw new InvalidArgumentException('Inactive item cannot be mapped to a supplier.');
        }

        if ($data->itemVariantId !== null) {
            $variant = ItemVariant::query()->findOrFail($data->itemVariantId);
            if ((int) $variant->item_id !== $data->itemId) {
                throw new InvalidArgumentException('Supplier item variant must belong to the mapped item.');
            }
            $this->assertScope(
                (int) $supplier->tenant_id,
                $supplier->organization_unit_id,
                (int) $variant->tenant_id,
                $variant->organization_unit_id,
            );
            if (! (bool) $variant->is_active) {
                throw new InvalidArgumentException('Inactive item variant cannot be mapped to a supplier.');
            }
        }

        if ($data->defaultPurchaseUomId !== null) {
            $uom = UnitOfMeasureModel::query()->findOrFail($data->defaultPurchaseUomId);
            $this->assertScope(
                (int) $supplier->tenant_id,
                $supplier->organization_unit_id,
                (int) $uom->tenant_id,
                $uom->organization_unit_id,
            );
            if (! (bool) $uom->is_active) {
                throw new InvalidArgumentException('Inactive UOM cannot be used for supplier item mapping.');
            }
        }
    }

    public function assertCurrencyActive(?int $currencyId): void
    {
        if ($currencyId === null) {
            return;
        }

        $currency = CurrencyModel::query()->findOrFail($currencyId);
        if (! (bool) $currency->is_active) {
            throw new InvalidArgumentException('Inactive currency cannot be used for supplier master data.');
        }
    }

    public function assertOrganizationUsable(int $tenantId, ?int $organizationUnitId): void
    {
        $this->assertOrganizationScope($tenantId, $organizationUnitId);
    }

    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void
    {
        $query = Supplier::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code);
        $this->ignoreKey($query, $ignoreId);
        if ($query->exists()) {
            throw new InvalidArgumentException('Supplier code already exists for this tenant.');
        }
    }

    private function assertNumberUnique(int $tenantId, string $number, ?int $ignoreId = null): void
    {
        $query = Supplier::query()->withTrashed()->where('tenant_id', $tenantId)->where('supplier_number', $number);
        $this->ignoreKey($query, $ignoreId);
        if ($query->exists()) {
            throw new InvalidArgumentException('Supplier number already exists for this tenant.');
        }
    }

    private function assertOrganizationScope(int $tenantId, ?int $organizationUnitId): void
    {
        if ($organizationUnitId === null) {
            return;
        }

        $organization = OrganizationUnitModel::query()->findOrFail($organizationUnitId);
        if ((int) $organization->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Supplier organization unit belongs to a different tenant.');
        }
        if (! (bool) $organization->is_active) {
            throw new InvalidArgumentException('Supplier organization unit must be active.');
        }
    }

    private function assertTaxNumbers(?string ...$numbers): void
    {
        foreach ($numbers as $number) {
            if ($number !== null && trim($number) !== '' && ! preg_match('/^[A-Za-z0-9.\/ -]{2,64}$/', $number)) {
                throw new InvalidArgumentException('Supplier tax and registration numbers contain invalid characters.');
            }
        }
    }

    private function assertScope(
        int $tenantId,
        ?int $organizationUnitId,
        int $recordTenantId,
        ?int $recordOrganizationUnitId,
    ): void {
        if ($recordTenantId !== $tenantId) {
            throw new InvalidArgumentException('Supplier reference belongs to a different tenant.');
        }
        if ($organizationUnitId !== null && $recordOrganizationUnitId !== null && (int) $recordOrganizationUnitId !== $organizationUnitId) {
            throw new InvalidArgumentException('Supplier reference belongs to a different organization unit.');
        }
    }

    private function assertNonNegative(string $value, string $message): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function assertText(string $value, string $message): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException($message);
        }
    }

    private function ignoreKey(Builder $query, ?int $id): void
    {
        if ($id !== null) {
            $query->whereKeyNot($id);
        }
    }
}
