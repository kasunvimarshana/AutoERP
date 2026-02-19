<?php

declare(strict_types=1);

namespace Modules\JobCard\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store JobPart Request
 *
 * Validates data for creating a new job part
 */
class StoreJobPartRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_item_id' => 'inventory item',
            'unit_price' => 'unit price',
        ];
    }
}
