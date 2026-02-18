<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Purchase Requisition Request
 *
 * Validates data for creating a new purchase requisition.
 */
class StorePurchaseRequisitionRequest extends FormRequest
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
            'requisition_number' => ['nullable', 'string', 'max:50', 'unique:purchase_requisitions,requisition_number'],
            'requester_id' => ['required', 'integer', 'exists:users,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'requested_date' => ['required', 'date'],
            'required_date' => ['nullable', 'date', 'after_or_equal:requested_date'],
            'status' => ['nullable', 'in:draft,submitted,approved,rejected,cancelled'],
            'approval_status' => ['nullable', 'in:pending,approved,rejected'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.product_id' => ['required_with:lines', 'integer', 'exists:products,id'],
            'lines.*.description' => ['nullable', 'string'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'min:0.0001'],
            'lines.*.unit_of_measure' => ['nullable', 'string', 'max:50'],
            'lines.*.estimated_unit_price' => ['required_with:lines', 'numeric', 'min:0'],
        ];
    }
}
