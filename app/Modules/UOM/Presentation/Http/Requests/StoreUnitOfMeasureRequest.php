<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitOfMeasureRequest extends FormRequest
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
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in(config('uom.types', []))],
            'is_base' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
