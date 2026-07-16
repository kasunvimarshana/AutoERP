<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\DTOs\CustomerCreditProfileData;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCreditProfile;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class CustomerCreditProfileService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function set(Customer $customer, CustomerCreditProfileData $data): CustomerCreditProfile
    {
        $this->validate($data);

        return DB::transaction(function () use ($customer, $data): CustomerCreditProfile {
            $profile = CustomerCreditProfile::query()
                ->where('tenant_id', (int) $customer->tenant_id)
                ->where('customer_id', (int) $customer->getKey())
                ->lockForUpdate()
                ->first();

            if ($profile instanceof CustomerCreditProfile) {
                if ($data->rowVersion === null || $data->rowVersion !== (int) $profile->row_version) {
                    throw new ConflictHttpException('Customer credit profile was changed by someone else. Reload before saving.');
                }

                $profile->forceFill($this->attributes($customer, $data) + [
                    'row_version' => ((int) $profile->row_version) + 1,
                ])->save();

                return $profile->refresh();
            }

            if ($data->rowVersion !== null) {
                throw new ConflictHttpException('Customer credit profile no longer exists. Reload before saving.');
            }

            $profile = new CustomerCreditProfile();
            $profile->forceFill($this->attributes($customer, $data) + [
                'row_version' => 1,
            ]);
            $profile->save();

            return $profile;
        }, 3);
    }

    public function get(Customer $customer): ?CustomerCreditProfile
    {
        return CustomerCreditProfile::query()
            ->where('tenant_id', (int) $customer->tenant_id)
            ->where('customer_id', (int) $customer->getKey())
            ->first();
    }

    private function validate(CustomerCreditProfileData $data): void
    {
        if ($this->math->isNegative($data->creditLimit)) {
            throw new InvalidArgumentException('Customer credit profile limit cannot be negative.');
        }
        if ($data->creditPeriodDays !== null && $data->creditPeriodDays < 0) {
            throw new InvalidArgumentException('Customer credit period cannot be negative.');
        }
        if ($this->math->isNegative($data->warningThresholdPercent)
            || $this->math->compare($data->warningThresholdPercent, '100.000000') > 0) {
            throw new InvalidArgumentException('Customer credit warning threshold must be between 0 and 100.');
        }
        if (! $data->creditAllowed && $data->allowOverCredit) {
            throw new InvalidArgumentException('Over-credit approval cannot be enabled when credit is disabled.');
        }
    }

    /** @return array<string, mixed> */
    private function attributes(Customer $customer, CustomerCreditProfileData $data): array
    {
        return [
            'tenant_id' => $customer->tenant_id,
            'organization_unit_id' => $customer->organization_unit_id,
            'customer_id' => $customer->getKey(),
            'credit_limit' => $this->math->normalize($data->creditLimit),
            'credit_period_days' => $data->creditPeriodDays,
            'warning_threshold_percent' => $this->math->normalize($data->warningThresholdPercent),
            'credit_allowed' => $data->creditAllowed,
            'advance_allowed' => $data->advanceAllowed,
            'allow_over_credit' => $data->allowOverCredit,
            'allow_partial_payment' => $data->allowPartialPayment,
            'is_active' => $data->isActive,
        ];
    }
}
