<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BridgeAssetAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) $this->input('tenant_id');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'org_unit_id' => [
                'nullable',
                'integer',
                Rule::exists('org_units', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'asset_id' => [
                'required',
                'integer',
                Rule::exists('assets', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'rental_booking_id' => [
                'required',
                'integer',
                Rule::exists('rental_bookings', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'reason_code' => 'nullable|string|max:120',
            'metadata' => 'nullable|array',
        ];
    }
}
