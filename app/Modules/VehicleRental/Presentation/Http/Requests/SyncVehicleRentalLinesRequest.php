<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SyncVehicleRentalLinesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'lines' => ['nullable', 'array'],
            'rates' => ['nullable', 'array'],
            'rate_rules' => ['nullable', 'array'],
            'extra_charges' => ['nullable', 'array'],
            'running_chart_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
