<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListVehicleRentalRunningChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'agreement_id' => ['nullable', 'integer', 'min:1'],
            'lessee_agreement_id' => ['nullable', 'integer', 'min:1'],
            'lessor_agreement_id' => ['nullable', 'integer', 'min:1'],
            'agreement_side' => ['nullable', 'string', 'max:40'],
        ];
    }
}
