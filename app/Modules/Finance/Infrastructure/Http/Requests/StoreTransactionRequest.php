<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Finance\Domain\ValueObjects\TransactionType;

/**
 * @OA\Schema(
 *   schema="StoreTransactionRequest",
 *   required={"type","transaction_date","amount"},
 *   @OA\Property(property="type", type="string", enum={"income","expense","transfer","payment","refund","adjustment"}),
 *   @OA\Property(property="transaction_date", type="string", format="date"),
 *   @OA\Property(property="amount", type="number", format="float"),
 *   @OA\Property(property="currency", type="string", example="USD"),
 *   @OA\Property(property="from_account_id", type="integer", nullable=true),
 *   @OA\Property(property="to_account_id", type="integer", nullable=true),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="category", type="string", nullable=true),
 * )
 */
final class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'              => ['required', 'string', 'in:' . implode(',', TransactionType::ALL)],
            'transaction_date'  => ['required', 'date'],
            'amount'            => ['required', 'numeric', 'min:0'],
            'currency'          => ['string', 'size:3'],
            'exchange_rate'     => ['numeric', 'min:0'],
            'journal_entry_id'  => ['nullable', 'integer', 'exists:journal_entries,id'],
            'from_account_id'   => ['nullable', 'integer', 'exists:accounts,id'],
            'to_account_id'     => ['nullable', 'integer', 'exists:accounts,id'],
            'description'       => ['nullable', 'string'],
            'category'          => ['nullable', 'string', 'max:100'],
            'tags'              => ['nullable', 'array'],
            'contact_type'      => ['nullable', 'string', 'max:100'],
            'contact_id'        => ['nullable', 'integer'],
            'attachments'       => ['nullable', 'array'],
            'metadata'          => ['nullable', 'array'],
        ];
    }
}
