<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreSupplierRequest",
 *   required={"name"},
 *   @OA\Property(property="name", type="string", maxLength=255, example="Acme Supplies Ltd"),
 *   @OA\Property(property="code", type="string", maxLength=50, nullable=true, example="SUP-001"),
 *   @OA\Property(property="email", type="string", format="email", nullable=true, example="supplier@acme.com"),
 *   @OA\Property(property="phone", type="string", maxLength=50, nullable=true, example="+1-555-0100"),
 *   @OA\Property(property="tax_number", type="string", maxLength=100, nullable=true, example="TX-123456"),
 *   @OA\Property(property="currency", type="string", minLength=3, maxLength=3, example="USD"),
 *   @OA\Property(property="payment_terms", type="integer", minimum=0, nullable=true, example=30),
 *   @OA\Property(property="credit_limit", type="number", format="float", nullable=true, example=50000.00),
 *   @OA\Property(property="address", type="object", nullable=true),
 *   @OA\Property(property="bank_details", type="object", nullable=true),
 *   @OA\Property(property="status", type="string", enum={"active","inactive","blocked"}, example="active"),
 *   @OA\Property(property="notes", type="string", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'code'          => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'tax_number'    => ['nullable', 'string', 'max:100'],
            'currency'      => ['sometimes', 'string', 'size:3'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'credit_limit'  => ['nullable', 'numeric', 'min:0'],
            'address'       => ['nullable', 'array'],
            'bank_details'  => ['nullable', 'array'],
            'status'        => ['sometimes', 'string', 'in:active,inactive,blocked'],
            'notes'         => ['nullable', 'string'],
            'metadata'      => ['nullable', 'array'],
        ];
    }
}
