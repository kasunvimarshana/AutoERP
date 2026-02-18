<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Unit of Measure Request
 *
 * Validates data for creating a new unit of measure.
 */
class StoreUnitOfMeasureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Inventory\Entities\UnitOfMeasure::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:unit_of_measures,code'],
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:100'],
            'uom_category' => ['required', 'string', 'max:50'],
            'ratio' => ['nullable', 'numeric', 'min:0.000001', 'max:999999'],
            'is_base_unit' => ['boolean'],
            'is_active' => ['boolean'],
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
            'name.en.required' => 'English name is required',
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
