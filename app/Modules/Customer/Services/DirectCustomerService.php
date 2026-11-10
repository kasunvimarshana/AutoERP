<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Support\Facades\DB;
use Modules\Customer\DTOs\CustomerCreditProfileData;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Models\Customer;
use Modules\Tenant\Models\TenantModel;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class DirectCustomerService
{
    public const CODE = 'CUS-DIRECT';

    private const NAME = 'Direct Customer';

    public function __construct(
        private readonly CustomerNumberService $numbers,
        private readonly CustomerCreditProfileService $creditProfiles,
        private readonly CustomerStatusService $statuses,
    ) {}

    public function ensure(int $tenantId): Customer
    {
        return DB::transaction(function () use ($tenantId): Customer {
            TenantModel::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $customer = Customer::query()
                ->withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('code', self::CODE)
                ->first();

            if ($customer !== null) {
                if ($customer->trashed() || $customer->status !== CustomerStatus::Active || $customer->name !== self::NAME || $customer->display_name !== self::NAME) {
                    throw new ConflictHttpException('Direct Customer record was changed. Restore it before creating a sale.');
                }

                return $customer->load('creditProfile');
            }

            $customer = Customer::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => null,
                'customer_number' => $this->numbers->next($tenantId),
                'code' => self::CODE,
                'name' => self::NAME,
                'display_name' => self::NAME,
                'customer_type' => CustomerType::Retail,
                'status' => CustomerStatus::Active,
                'approved_at' => now(),
            ]);
            $this->creditProfiles->set($customer, new CustomerCreditProfileData(creditAllowed: false));
            $this->statuses->recordInitial($customer);

            return $customer->load('creditProfile');
        });
    }
}
