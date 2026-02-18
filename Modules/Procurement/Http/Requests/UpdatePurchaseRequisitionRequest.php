<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Purchase Requisition Request
 *
 * Validates data for updating a purchase requisition.
 */
class UpdatePurchaseRequisitionRequest extends FormRequest
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
        $requisitionId = $this->route('id');

        return [
            'requisition_number' => ['nullable', 'string', 'max:50', 'unique:purchase_requisitions,requisition_number,'.$requisitionId],
            'requester_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'requested_date' => ['sometimes', 'required', 'date'],
            'required_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,submitted,approved,rejected,cancelled'],
            'approval_status' => ['nullable', 'in:pending,approved,rejected'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'rejection_reason' => ['nullable', 'string'],
        ];
    }
}
