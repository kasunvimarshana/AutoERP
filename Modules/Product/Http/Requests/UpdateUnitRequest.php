<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Unit Request
 */
class UpdateUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('unit'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'symbol' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'symbol')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->ignore($unit->id),
            ],
            'type' => ['sometimes', 'required', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'unit name',
            'symbol' => 'unit symbol',
            'type' => 'unit type',
        ];
    }
}
