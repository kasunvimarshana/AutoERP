<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Supplier\DTOs\SupplierCreditProfileData;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierCreditProfile;

final class SupplierCreditProfileService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function set(Supplier $supplier, SupplierCreditProfileData $data): SupplierCreditProfile
    {
        $this->validate($data);

        return SupplierCreditProfile::query()->updateOrCreate(
            ['supplier_id' => $supplier->getKey()],
            [
                'tenant_id' => $supplier->tenant_id,
                'organization_unit_id' => $supplier->organization_unit_id,
                'credit_limit' => $this->math->normalize($data->creditLimit),
                'credit_period_days' => $data->creditPeriodDays,
                'warning_threshold_percent' => $this->math->normalize($data->warningThresholdPercent),
                'allow_over_credit' => $data->allowOverCredit,
                'allow_partial_payment' => $data->allowPartialPayment,
                'is_active' => $data->isActive,
            ],
        );
    }

    public function get(Supplier $supplier): ?SupplierCreditProfile
    {
        return $supplier->creditProfile()->first();
    }

    private function validate(SupplierCreditProfileData $data): void
    {
        if ($this->math->isNegative($data->creditLimit)) {
            throw new InvalidArgumentException('Supplier credit profile limit cannot be negative.');
        }
        if ($data->creditPeriodDays !== null && $data->creditPeriodDays < 0) {
            throw new InvalidArgumentException('Supplier credit period cannot be negative.');
        }
        if ($this->math->isNegative($data->warningThresholdPercent)
            || $this->math->compare($data->warningThresholdPercent, '100.000000') > 0) {
            throw new InvalidArgumentException('Supplier credit warning threshold must be between 0 and 100.');
        }
    }
}
