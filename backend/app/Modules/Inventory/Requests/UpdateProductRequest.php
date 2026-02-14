<?php

namespace App\Modules\Inventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Product Request
 * 
 * Validation rules for updating a product
 * 
 * @package App\Modules\Inventory\Requests
 */
class UpdateProductRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'unit_of_measure' => 'sometimes|required|string|max:50',
            'status' => 'sometimes|required|string|in:active,inactive,discontinued',
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
            'status.in' => 'The status must be one of: active, inactive, discontinued.',
        ];
    }
}
