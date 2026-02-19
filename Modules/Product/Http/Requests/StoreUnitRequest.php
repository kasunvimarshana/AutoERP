<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Unit Request
 */
class StoreUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Product\Models\Unit::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'symbol' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'symbol')
                    ->where('tenant_id', $this->user()->currentTenant()->id),
            ],
            'type' => ['required', 'string', 'max:50'],
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
