<?php

declare(strict_types=1);

namespace Modules\Customer\Validators;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\DTOs\UpdateCustomerData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCategory;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CustomerValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function validateCreate(CreateCustomerData $data): void
    {
        $this->assertText($data->code, 'Customer code is required.');
        $this->assertText($data->name, 'Customer name is required.');
        $this->assertCodeUnique($data->tenantId, $data->code);
        if ($data->customerNumber !== null) {
            $this->assertNumberUnique($data->tenantId, $data->customerNumber);
        }
        $this->assertNonNegative($data->creditLimit, 'Customer credit limit cannot be negative.');
        $this->assertOrganizationScope($data->tenantId, $data->organizationUnitId);
        $this->assertCurrencyActive($data->defaultCurrencyId);
        $this->assertTaxNumbers($data->taxRegistrationNumber, $data->vatNumber, $data->svatNumber, $data->businessRegistrationNumber);
        if ($data->creditProfile !== null) {
            $this->assertNonNegative($data->creditProfile->creditLimit, 'Customer credit profile limit cannot be negative.');
        }
    }

    public function validateUpdate(Customer $customer, UpdateCustomerData $data): void
    {
        if ($data->code !== null) {
            $this->assertText($data->code, 'Customer code is required.');
            $this->assertCodeUnique((int) $customer->tenant_id, $data->code, (int) $customer->getKey());
        }
        if ($data->name !== null) {
            $this->assertText($data->name, 'Customer name is required.');
        }
        if ($data->creditLimit !== null) {
            $this->assertNonNegative($data->creditLimit, 'Customer credit limit cannot be negative.');
        }
        $this->assertOrganizationScope((int) $customer->tenant_id, $data->organizationUnitId);
        $this->assertCurrencyActive($data->defaultCurrencyId);
        $this->assertTaxNumbers($data->taxRegistrationNumber, $data->vatNumber, $data->svatNumber, $data->businessRegistrationNumber);
        if ($data->creditProfile !== null) {
            $this->assertNonNegative($data->creditProfile->creditLimit, 'Customer credit profile limit cannot be negative.');
        }
    }

    public function assertCustomerScope(Customer $customer, int $tenantId, ?int $organizationUnitId): void
    {
        $this->assertScope(
            $tenantId,
            $organizationUnitId,
            (int) $customer->tenant_id,
            $customer->organization_unit_id,
        );
    }

    public function assertCategoryUsable(Customer $customer, int $categoryId): CustomerCategory
    {
        $category = CustomerCategory::query()->find($categoryId);
        if (! $category instanceof CustomerCategory) {
            $owner = DB::table('customer_categories')
                ->where('id', $categoryId)
                ->whereNull('deleted_at')
                ->first(['tenant_id', 'organization_unit_id']);

            if ($owner !== null) {
                $this->assertScope(
                    (int) $customer->tenant_id,
                    $customer->organization_unit_id,
                    (int) $owner->tenant_id,
                    $owner->organization_unit_id === null ? null : (int) $owner->organization_unit_id,
                );
            }

            throw new InvalidArgumentException('Customer category was not found.');
        }
        $this->assertScope(
            (int) $customer->tenant_id,
            $customer->organization_unit_id,
            (int) $category->tenant_id,
            $category->organization_unit_id,
        );
        if (! (bool) $category->is_active) {
            throw new InvalidArgumentException('Inactive customer category cannot be assigned.');
        }

        return $category;
    }

    public function assertCurrencyActive(?int $currencyId): void
    {
        if ($currencyId === null) {
            return;
        }

        $currency = CurrencyModel::query()->findOrFail($currencyId);
        if (! (bool) $currency->is_active) {
            throw new InvalidArgumentException('Inactive currency cannot be used for customer master data.');
        }
    }

    public function assertOrganizationUsable(int $tenantId, ?int $organizationUnitId): void
    {
        $this->assertOrganizationScope($tenantId, $organizationUnitId);
    }

    private function assertCodeUnique(int $tenantId, string $code, ?int $ignoreId = null): void
    {
        $query = Customer::query()->withTrashed()->where('tenant_id', $tenantId)->where('code', $code);
        $this->ignoreKey($query, $ignoreId);
        if ($query->exists()) {
            throw new ConflictHttpException('Customer code already exists for this tenant.');
        }
    }

    private function assertNumberUnique(int $tenantId, string $number, ?int $ignoreId = null): void
    {
        $query = Customer::query()->withTrashed()->where('tenant_id', $tenantId)->where('customer_number', $number);
        $this->ignoreKey($query, $ignoreId);
        if ($query->exists()) {
            throw new ConflictHttpException('Customer number already exists for this tenant.');
        }
    }

    private function assertOrganizationScope(int $tenantId, ?int $organizationUnitId): void
    {
        if ($organizationUnitId === null) {
            return;
        }

        $organization = OrganizationUnitModel::query()->findOrFail($organizationUnitId);
        if ((int) $organization->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Customer organization unit belongs to a different tenant.');
        }
        if (! (bool) $organization->is_active) {
            throw new InvalidArgumentException('Customer organization unit must be active.');
        }
    }

    private function assertTaxNumbers(?string ...$numbers): void
    {
        foreach ($numbers as $number) {
            if ($number !== null && trim($number) !== '' && ! preg_match('/^[A-Za-z0-9.\/ -]{2,64}$/', $number)) {
                throw new InvalidArgumentException('Customer tax and registration numbers contain invalid characters.');
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
            throw new InvalidArgumentException('Customer reference belongs to a different tenant.');
        }
        if (($organizationUnitId === null && $recordOrganizationUnitId !== null)
            || ($organizationUnitId !== null && $recordOrganizationUnitId !== null && (int) $recordOrganizationUnitId !== $organizationUnitId)) {
            throw new InvalidArgumentException('Customer reference belongs to a different organization unit.');
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
