<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemBrandRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'sometimes|nullable|integer|exists:tenants,id',
            'organization_unit_id' => 'sometimes|nullable|integer|exists:organization_units,id',

            'parent_id' => 'nullable|integer|exists:item_brands,id',
            'name' => 'required|string',
            'slug' => 'required|string',
            'code' => 'required|string',
            'path' => 'required|string',
            'image_path' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'depth' => 'required|integer',
            'is_active' => 'required|boolean',
            'website' => 'required|string',
            'description' => 'required|string'
        ];
    }
}
