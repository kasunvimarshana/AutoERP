<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Requests;

final class UpdateRentalAgreementRequest extends StoreRentalAgreementRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'expected_version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function expectedVersion(): int
    {
        return (int) $this->validated('expected_version');
    }
}
