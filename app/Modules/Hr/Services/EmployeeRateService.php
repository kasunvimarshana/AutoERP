<?php

declare(strict_types=1);

namespace Modules\Hr\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\DTOs\EmployeeRateData;
use Modules\Hr\Enums\EmployeeRateType;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeRate;
use Modules\ReferenceData\Models\CurrencyModel;

final class EmployeeRateService
{
    public function __construct(private readonly DecimalMath $math, private readonly EmployeeValidationService $validator) {}

    public function create(HrEmployee $employee, EmployeeRateData $data): HrEmployeeRate
    {
        $this->validate($employee, $data);

        return $employee->rates()->create($this->attributes($employee, $data));
    }

    public function activeRate(HrEmployee $employee, EmployeeRateType $type, ?string $date = null): ?HrEmployeeRate
    {
        $date ??= now()->toDateString();

        return $employee->rates()
            ->where('rate_type', $type)
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('effective_from')->orWhere('effective_from', '<=', $date))
            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderByDesc('effective_from')
            ->first();
    }

    private function validate(HrEmployee $employee, EmployeeRateData $data): void
    {
        if ($this->math->isNegative($data->amount)) {
            throw new InvalidArgumentException('Employee rate cannot be negative.');
        }

        $this->validator->assertDateRange($data->effectiveFrom, $data->effectiveTo);

        if ($data->currencyId !== null) {
            $currency = CurrencyModel::query()->findOrFail($data->currencyId);
            if (! (bool) $currency->is_active) {
                throw new InvalidArgumentException('Inactive currency cannot be used for an employee rate.');
            }
        }

        if (! $data->isActive) {
            return;
        }

        $query = $employee->rates()
            ->where('rate_type', $data->rateType)
            ->where('is_active', true);

        $query->where(function (Builder $overlap) use ($data): void {
            $overlap
                ->where(fn (Builder $q) => $data->effectiveTo === null ? $q : $q->whereNull('effective_from')->orWhere('effective_from', '<=', $data->effectiveTo))
                ->where(fn (Builder $q) => $data->effectiveFrom === null ? $q : $q->whereNull('effective_to')->orWhere('effective_to', '>=', $data->effectiveFrom));
        });

        if ($query->exists()) {
            throw new InvalidArgumentException('Active employee rates of the same type cannot overlap.');
        }
    }

    private function attributes(HrEmployee $employee, EmployeeRateData $data): array
    {
        return [
            'tenant_id' => $employee->tenant_id,
            'organization_unit_id' => $employee->organization_unit_id,
            'rate_type' => $data->rateType,
            'amount' => $this->math->normalize($data->amount),
            'currency_id' => $data->currencyId,
            'effective_from' => $data->effectiveFrom,
            'effective_to' => $data->effectiveTo,
            'is_active' => $data->isActive,
        ];
    }
}
