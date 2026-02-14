<?php

namespace App\Modules\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="UpdateCustomerRequest",
 *
 *     @OA\Property(property="type", type="string", enum={"individual", "business"}),
 *     @OA\Property(property="first_name", type="string"),
 *     @OA\Property(property="last_name", type="string"),
 *     @OA\Property(property="company_name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive", "blocked"})
 * )
 */
class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add proper authorization logic
    }

    public function rules(): array
    {
        $customerId = $this->route('id');

        return [
            'type' => 'sometimes|in:individual,business',
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company_name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:customers,email,{$customerId}",
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'status' => 'sometimes|in:active,inactive,blocked',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
        ];
    }
}
