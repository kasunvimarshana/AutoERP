<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListVehicleServiceNonInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'job_card_id' => ['nullable', 'integer', 'min:1', 'exists:vehicle_service_job_cards,id'],
            'source_type' => ['nullable', 'string', 'max:80'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('vehicle_service.pagination.max_per_page', 200)],
        ];
    }
}
