<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->input('tenant_id');
        $unitOfMeasureId = (int) $this->route('unit_of_measure');

        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'symbol' => [
                'required',
                'string',
                'max:10',
                Rule::unique('units_of_measure', 'symbol')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($unitOfMeasureId),
            ],
            'type' => 'nullable|string|in:unit,mass,volume,length,time,other',
            'is_base' => 'nullable|boolean',
            'row_version' => 'required|integer|min:1',
        ];
    }
}
