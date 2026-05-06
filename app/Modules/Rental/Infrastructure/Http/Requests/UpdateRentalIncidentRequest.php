<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRentalIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incident_type' => ['sometimes', 'string', 'in:damage,traffic_violation,late_return,other'],
            'status' => ['sometimes', 'string', 'in:open,under_review,resolved,waived'],
            'occurred_at' => ['sometimes', 'date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'estimated_cost' => ['sometimes', 'numeric', 'min:0'],
            'recovered_amount' => ['sometimes', 'numeric', 'min:0'],
            'recovery_status' => ['sometimes', 'string', 'in:none,partial,full'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
