<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Fleet\Domain\ValueObjects\VehicleState;

class ChangeVehicleStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $valid = implode(',', [
            VehicleState::AVAILABLE,
            VehicleState::RENTED,
            VehicleState::IN_SERVICE,
            VehicleState::MAINTENANCE,
            VehicleState::RETIRED,
        ]);

        return [
            'to_state'       => ['required', 'string', "in:{$valid}"],
            'reason'         => ['required', 'string', 'max:500'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id'   => ['nullable', 'integer'],
        ];
    }
}
