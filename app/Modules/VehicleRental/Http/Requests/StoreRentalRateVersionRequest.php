<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\DTOs\RentalRateLineData;
use Modules\VehicleRental\DTOs\RentalRateVersionData;
use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;

final class StoreRentalRateVersionRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'rates' => ['required', 'array', 'min:1'],
            'rates.*.code' => ['required', Rule::enum(RentalRateCode::class), 'distinct'],
            'rates.*.unit' => ['required', Rule::enum(RentalRateUnit::class)],
            'rates.*.rate' => ['required', 'numeric', 'min:0'],
            'rates.*.is_taxable' => ['required', 'boolean'],
            'rates.*.description' => ['nullable', 'string', 'max:255'],
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function toData(): RentalRateVersionData
    {
        $rates = [];
        foreach ($this->validated('rates') as $rate) {
            $description = isset($rate['description']) ? trim((string) $rate['description']) : '';
            $rates[] = new RentalRateLineData(
                code: RentalRateCode::from((string) $rate['code']),
                unit: RentalRateUnit::from((string) $rate['unit']),
                rate: (string) $rate['rate'],
                isTaxable: (bool) $rate['is_taxable'],
                description: $description === '' ? null : $description,
            );
        }

        return new RentalRateVersionData(
            effectiveFrom: (string) $this->validated('effective_from'),
            rates: $rates,
            actorId: $this->currentUserId(),
        );
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('expected_version');
    }
}
