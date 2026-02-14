<?php

namespace App\Modules\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     schema="CreateCustomerRequest",
 *     required={"type", "email"},
 *
 *     @OA\Property(property="type", type="string", enum={"individual", "business"}),
 *     @OA\Property(property="first_name", type="string"),
 *     @OA\Property(property="last_name", type="string"),
 *     @OA\Property(property="company_name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="phone", type="string"),
 *     @OA\Property(property="mobile", type="string"),
 *     @OA\Property(property="tax_id", type="string"),
 *     @OA\Property(property="billing_address", type="string"),
 *     @OA\Property(property="shipping_address", type="string"),
 *     @OA\Property(property="city", type="string"),
 *     @OA\Property(property="state", type="string"),
 *     @OA\Property(property="country", type="string"),
 *     @OA\Property(property="postal_code", type="string"),
 *     @OA\Property(property="credit_limit", type="number", format="float"),
 *     @OA\Property(property="payment_terms_days", type="integer")
 * )
 */
class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Add proper authorization logic
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:individual,business',
            'first_name' => 'required_if:type,individual|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'company_name' => 'required_if:type,business|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms_days' => 'nullable|integer|min:0',
            'contacts' => 'nullable|array',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.phone' => 'nullable|string|max:20',
            'contacts.*.title' => 'nullable|string|max:100',
        ];
    }
}
