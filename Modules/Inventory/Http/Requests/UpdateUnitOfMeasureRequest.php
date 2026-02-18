<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Unit of Measure Request
 *
 * Validates data for updating an existing unit of measure.
 */
class UpdateUnitOfMeasureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $uom = $this->route('unit_of_measure');
        return $this->user()->can('update', $uom);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $uomId = $this->route('unit_of_measure');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('unit_of_measures', 'code')->ignore($uomId),
            ],
            'name' => ['sometimes', 'required', 'array'],
            'name.en' => ['required_with:name', 'string', 'max:100'],
            'uom_category' => ['sometimes', 'required', 'string', 'max:50'],
            'ratio' => ['sometimes', 'nullable', 'numeric', 'min:0.000001', 'max:999999'],
            'is_base_unit' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Unit of measure code is required',
            'code.unique' => 'This unit of measure code already exists',
            'name.required' => 'Unit of measure name is required',
            'name.array' => 'Unit of measure name must be a translatable object',
            'name.en.required_with' => 'English name is required when updating name',
            'uom_category.required' => 'Unit of measure category is required',
            'ratio.min' => 'Ratio must be greater than zero',
            'ratio.max' => 'Ratio is too large',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'code' => 'UoM code',
            'name' => 'UoM name',
            'uom_category' => 'UoM category',
            'ratio' => 'conversion ratio',
            'is_base_unit' => 'base unit flag',
            'is_active' => 'active status',
        ];
    }
}
