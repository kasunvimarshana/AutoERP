<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Product Request
 * 
 * Validation rules for creating a new product
 * 
 * @package App\Modules\Inventory\Requests
 */
class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'unit_of_measure' => 'required|string|max:50',
            'status' => 'required|string|in:active,inactive,discontinued',
        ];
    }

    /**
     * Get custom messages for validator errors
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'The selected tenant does not exist.',
            'status.in' => 'The status must be one of: active, inactive, discontinued.',
        ];
    }
}
