<?php

declare(strict_types=1);

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $vendor = \App\Modules\Procurement\Models\Vendor::find($this->route('id'));
        return $vendor && $this->user()->can('update', $vendor);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $vendorId = $this->route('id');
        
        return [
            'type' => ['sometimes', 'in:individual,business'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:vendors,code,' . $vendorId],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'mobile' => ['sometimes', 'string', 'max:20'],
            'website' => ['sometimes', 'url', 'max:255'],
            'tax_number' => ['sometimes', 'string', 'max:50'],
            'company_name' => ['sometimes', 'string', 'max:255'],
            'industry' => ['sometimes', 'string', 'max:100'],
            'established_date' => ['sometimes', 'date'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'payment_terms_type' => ['sometimes', 'in:net,eom,cod,advance'],
            'currency_id' => ['sometimes', 'integer', 'exists:currencies,id'],
            'payment_method' => ['sometimes', 'in:cash,credit_card,bank_transfer,cheque,other'],
            'bank_name' => ['sometimes', 'string', 'max:255'],
            'bank_account_number' => ['sometimes', 'string', 'max:100'],
            'bank_branch' => ['sometimes', 'string', 'max:255'],
            'swift_code' => ['sometimes', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'is_verified' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:active,inactive,suspended,blacklisted'],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'vendor_category' => ['sometimes', 'string', 'max:100'],
            'source' => ['sometimes', 'string', 'max:100'],
            'assigned_to' => ['sometimes', 'integer', 'exists:users,id'],
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'branch_id' => ['sometimes', 'integer', 'exists:branches,id'],
            'notes' => ['sometimes', 'string'],
            'tags' => ['sometimes', 'array'],
            'custom_fields' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
