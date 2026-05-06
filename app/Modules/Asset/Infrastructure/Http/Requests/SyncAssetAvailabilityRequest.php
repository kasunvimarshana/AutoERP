<?php

declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncAssetAvailabilityRequest extends FormRequest
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
            'target_status' => 'required|in:available,reserved,rented,in_service,internal_use,blocked',
            'reason_code' => 'nullable|string|max:120',
            'source_type' => 'nullable|string|max:100',
            'source_id' => 'nullable|integer',
            'changed_by' => 'nullable|integer|exists:users,id',
            'metadata' => 'nullable|array',
        ];
    }
}
