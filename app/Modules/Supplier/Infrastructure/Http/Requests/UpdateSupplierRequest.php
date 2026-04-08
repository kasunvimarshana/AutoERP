<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="UpdateSupplierRequest",
 *   @OA\Property(property="name", type="string", maxLength=255, nullable=true),
 *   @OA\Property(property="code", type="string", maxLength=50, nullable=true),
 *   @OA\Property(property="email", type="string", format="email", nullable=true),
 *   @OA\Property(property="phone", type="string", maxLength=50, nullable=true),
 *   @OA\Property(property="tax_number", type="string", maxLength=100, nullable=true),
 *   @OA\Property(property="currency", type="string", minLength=3, maxLength=3, nullable=true),
 *   @OA\Property(property="payment_terms", type="integer", minimum=0, nullable=true),
 *   @OA\Property(property="credit_limit", type="number", format="float", nullable=true),
 *   @OA\Property(property="address", type="object", nullable=true),
 *   @OA\Property(property="bank_details", type="object", nullable=true),
 *   @OA\Property(property="status", type="string", enum={"active","inactive","blocked"}, nullable=true),
 *   @OA\Property(property="notes", type="string", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 * )
 */
final class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'code'          => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'tax_number'    => ['nullable', 'string', 'max:100'],
            'currency'      => ['nullable', 'string', 'size:3'],
            'payment_terms' => ['nullable', 'integer', 'min:0'],
            'credit_limit'  => ['nullable', 'numeric', 'min:0'],
            'address'       => ['nullable', 'array'],
            'bank_details'  => ['nullable', 'array'],
            'status'        => ['nullable', 'string', 'in:active,inactive,blocked'],
            'notes'         => ['nullable', 'string'],
            'metadata'      => ['nullable', 'array'],
        ];
    }
}
