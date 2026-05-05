<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer'],
            'org_unit_id' => ['nullable', 'integer'],
            'asset_name' => ['nullable', 'string', 'max:255'],
            'usage_mode' => ['nullable', 'string', 'in:rent_only,service_only,dual_use,internal_only'],
            'lifecycle_status' => ['nullable', 'string', 'in:active,inactive,retired,disposed'],
            'rental_status' => ['nullable', 'string', 'in:available,reserved,rented,maintenance,unavailable'],
            'service_status' => ['nullable', 'string', 'in:available,in_service,waiting_parts,unavailable'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'chassis_number' => ['nullable', 'string', 'max:100'],
            'engine_number' => ['nullable', 'string', 'max:100'],
            'year_of_manufacture' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'make' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', 'string', 'in:petrol,diesel,electric,hybrid,cng,lpg,other'],
            'purchase_cost' => ['nullable', 'numeric'],
            'book_value' => ['nullable', 'numeric'],
            'purchase_date' => ['nullable', 'date'],
            'current_odometer' => ['nullable', 'numeric'],
            'engine_hours' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
