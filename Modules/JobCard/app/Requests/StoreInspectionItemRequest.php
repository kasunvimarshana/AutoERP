<?php

declare(strict_types=1);

namespace Modules\JobCard\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\JobCard\Enums\InspectionCondition;

/**
 * Store InspectionItem Request
 *
 * Validates data for creating a new inspection item
 */
class StoreInspectionItemRequest extends FormRequest
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
            'item_type' => ['required', 'string', 'max:100'],
            'item_name' => ['required', 'string', 'max:255'],
            'condition' => ['required', Rule::in(InspectionCondition::values())],
            'notes' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['string'],
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
            'item_type' => 'item type',
            'item_name' => 'item name',
        ];
    }
}
