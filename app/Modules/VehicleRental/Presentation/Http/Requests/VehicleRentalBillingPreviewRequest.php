<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class VehicleRentalBillingPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'effective_at' => ['nullable', 'date'],
            'running_chart_id' => ['nullable', 'integer', 'min:1'],
            'running_chart_lines' => ['nullable', 'array'],
            'base_quantity' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
