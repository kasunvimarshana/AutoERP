<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Purchase Requisition Line Request
 *
 * Validates data for updating a purchase requisition line.
 */
class UpdatePurchaseRequisitionLineRequest extends FormRequest
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
            'purchase_requisition_id' => ['sometimes', 'required', 'integer', 'exists:purchase_requisitions,id'],
            'product_id' => ['sometimes', 'required', 'integer', 'exists:products,id'],
            'description' => ['nullable', 'string'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.0001'],
            'unit_of_measure' => ['nullable', 'string', 'max:50'],
            'estimated_unit_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
