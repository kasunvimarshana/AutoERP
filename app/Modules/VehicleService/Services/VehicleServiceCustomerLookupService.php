<?php

declare(strict_types=1);

namespace Modules\VehicleService\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Customer\Models\Customer;
use Modules\VehicleService\DTOs\VehicleServiceCustomerData;
use Modules\VehicleService\Enums\VehicleServiceCustomerStatus;
use Modules\VehicleService\Enums\VehicleServiceCustomerType;
use Modules\VehicleService\Enums\VehicleServiceOwnerType;
use Modules\VehicleService\Models\VehicleServiceCustomerProfile;

final class VehicleServiceCustomerLookupService
{
    public function lookup(
        int $tenantId,
        ?int $organizationUnitId,
        string $search,
        int $perPage,
        ?string $customerType = null,
        ?string $status = null,
    ): LengthAwarePaginator {
        $search = trim($search);

        $query = Customer::query()
            ->where('tenant_id', $tenantId)
            ->with(['creditProfile', 'defaultCurrency', 'organizationUnit'])
            ->when(
                $organizationUnitId === null,
                static fn (Builder $scope): Builder => $scope->whereNull('organization_unit_id'),
                static fn (Builder $scope): Builder => $scope->where(function (Builder $organizationScope) use ($organizationUnitId): void {
                    $organizationScope->whereNull('organization_unit_id')
                        ->orWhere('organization_unit_id', $organizationUnitId);
                }),
            );

        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('customer_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($customerType !== null && trim($customerType) !== '') {
            $query->where('customer_type', trim($customerType));
        }

        if ($status !== null && trim($status) !== '') {
            $query->where('status', trim($status));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function one(int $tenantId, int $customerId): VehicleServiceCustomerData
    {
        $customer = Customer::query()
            ->where('tenant_id', $tenantId)
            ->with(['creditProfile', 'defaultCurrency', 'organizationUnit'])
            ->findOrFail($customerId);

        return $this->data($customer);
    }

    public function profile(int $tenantId, int $customerId): VehicleServiceCustomerProfile
    {
        return VehicleServiceCustomerProfile::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'customer_id' => $customerId],
            ['row_version' => 1, 'is_active' => true],
        );
    }

    public function updateProfile(
        int $tenantId,
        int $customerId,
        int $expectedVersion,
        array $attributes,
    ): VehicleServiceCustomerProfile {
        return DB::transaction(function () use ($tenantId, $customerId, $expectedVersion, $attributes): VehicleServiceCustomerProfile {
            $profile = VehicleServiceCustomerProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('customer_id', $customerId)
                ->lockForUpdate()
                ->first();

            if (! $profile instanceof VehicleServiceCustomerProfile) {
                $profile = VehicleServiceCustomerProfile::query()->create([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customerId,
                    'row_version' => 1,
                    'is_active' => true,
                ]);
            }

            if ((int) $profile->row_version !== $expectedVersion) {
                throw new InvalidArgumentException('Vehicle Service customer profile was modified by another request.');
            }

            $profile->forceFill([
                ...$attributes,
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $profile->refresh();
        }, 3);
    }

    private function data(Customer $customer): VehicleServiceCustomerData
    {
        $creditProfile = $customer->creditProfile;

        return new VehicleServiceCustomerData(
            id: (int) $customer->getKey(),
            customerNumber: (string) $customer->customer_number,
            code: (string) $customer->code,
            name: (string) $customer->name,
            customerType: VehicleServiceCustomerType::from((string) $customer->customer_type),
            status: VehicleServiceCustomerStatus::from((string) $customer->status),
            ownerType: VehicleServiceOwnerType::Customer,
            email: $customer->email,
            phone: $customer->phone,
            mobile: $customer->mobile,
            currencyId: $customer->default_currency_id,
            creditLimit: (string) ($creditProfile?->credit_limit ?? '0.000000'),
            creditAllowed: (bool) ($creditProfile?->credit_allowed ?? false),
            advanceAllowed: (bool) ($creditProfile?->advance_allowed ?? false),
            organizationUnitId: $customer->organization_unit_id,
            organizationUnitName: $customer->organizationUnit?->name,
        );
    }
}
