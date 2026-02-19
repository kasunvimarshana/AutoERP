<?php

declare(strict_types=1);

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store Unit Conversion Request
 */
class StoreUnitConversionRequest extends FormRequest
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
        return [
            'to_unit_id' => [
                'required',
                Rule::exists('units', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id),
                'different:from_unit_id',
            ],
            'conversion_factor' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'from_unit_id' => $this->route('unit')->id,
        ]);
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'to_unit_id.different' => 'Cannot create conversion from a unit to itself.',
            'conversion_factor.gt' => 'Conversion factor must be greater than zero.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'to_unit_id' => 'destination unit',
            'conversion_factor' => 'conversion factor',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $fromUnit = $this->route('unit');

            if ($this->to_unit_id) {
                $toUnit = \Modules\Product\Models\Unit::find($this->to_unit_id);

                if ($toUnit && $fromUnit->type !== $toUnit->type) {
                    $validator->errors()->add(
                        'to_unit_id',
                        'Can only convert between units of the same type.'
                    );
                }

                $exists = \Modules\Product\Models\ProductUnitConversion::where('from_unit_id', $fromUnit->id)
                    ->where('to_unit_id', $this->to_unit_id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'to_unit_id',
                        'A conversion between these units already exists.'
                    );
                }
            }
        });
    }
}
