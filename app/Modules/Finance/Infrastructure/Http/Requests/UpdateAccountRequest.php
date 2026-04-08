<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Finance\Domain\ValueObjects\AccountType;

/**
 * @OA\Schema(
 *   schema="UpdateAccountRequest",
 *   @OA\Property(property="code", type="string", maxLength=20),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="type", type="string", enum={"asset","liability","equity","revenue","expense"}),
 *   @OA\Property(property="nature", type="string", enum={"debit","credit"}, nullable=true),
 *   @OA\Property(property="classification", type="string", nullable=true),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="is_active", type="boolean"),
 *   @OA\Property(property="currency", type="string"),
 * )
 */
final class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id'           => ['nullable', 'integer', 'exists:accounts,id'],
            'code'                => ['sometimes', 'required', 'string', 'max:20'],
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'type'                => ['sometimes', 'required', 'string', 'in:' . implode(',', AccountType::ALL)],
            'nature'              => ['nullable', 'string', 'in:debit,credit'],
            'classification'      => ['nullable', 'string', 'max:100'],
            'description'         => ['nullable', 'string'],
            'is_active'           => ['boolean'],
            'is_bank_account'     => ['boolean'],
            'bank_name'           => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_routing_number' => ['nullable', 'string', 'max:50'],
            'currency'            => ['string', 'size:3'],
            'opening_balance'     => ['numeric'],
            'metadata'            => ['nullable', 'array'],
        ];
    }
}
