<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;

final class UpsertVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = app(CurrentTenantContextAccessorInterface::class)->currentTenantId();
        $vehicleId = $this->route('vehicle');
        $required = $this->isMethod('patch') ? ['sometimes'] : ['required'];

        return [
            'organization_unit_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('organization_units', 'id')->where('tenant_id', $tenantId),
            ],
            'vehicle_code' => [
                ...$required,
                'string',
                'max:60',
                Rule::unique('vehicles', 'vehicle_code')
                    ->where('tenant_id', $tenantId)
                    ->ignore($vehicleId),
            ],
            'registration_number' => [
                ...$required,
                'string',
                'max:100',
                Rule::unique('vehicles', 'registration_number')
                    ->where('tenant_id', $tenantId)
                    ->ignore($vehicleId),
            ],
            'chassis_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'engine_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'make' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model' => ['sometimes', 'nullable', 'string', 'max:255'],
            'year' => ['sometimes', 'nullable', 'integer', 'min:1886', 'max:'.(now()->year + 1)],
            'color' => ['sometimes', 'nullable', 'string', 'max:100'],
            'vehicle_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'fuel_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'transmission_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'ownership_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => [...$required, Rule::in(['active', 'inactive'])],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
