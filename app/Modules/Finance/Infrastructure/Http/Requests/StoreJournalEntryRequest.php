<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *   schema="StoreJournalEntryRequest",
 *   required={"entry_date","lines"},
 *   @OA\Property(property="entry_date", type="string", format="date", example="2026-04-04"),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="currency", type="string", example="USD"),
 *   @OA\Property(property="source_type", type="string", nullable=true),
 *   @OA\Property(property="source_id", type="integer", nullable=true),
 *   @OA\Property(
 *     property="lines",
 *     type="array",
 *     minItems=2,
 *     @OA\Items(
 *       @OA\Property(property="account_id", type="integer"),
 *       @OA\Property(property="debit_amount", type="number", format="float"),
 *       @OA\Property(property="credit_amount", type="number", format="float"),
 *       @OA\Property(property="description", type="string", nullable=true),
 *     )
 *   )
 * )
 */
final class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_date'              => ['required', 'date'],
            'description'             => ['nullable', 'string'],
            'currency'                => ['string', 'size:3'],
            'source_type'             => ['nullable', 'string', 'max:100'],
            'source_id'               => ['nullable', 'integer'],
            'metadata'                => ['nullable', 'array'],
            'lines'                   => ['required', 'array', 'min:2'],
            'lines.*.account_id'      => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit_amount'    => ['required_without:lines.*.credit_amount', 'numeric', 'min:0'],
            'lines.*.credit_amount'   => ['required_without:lines.*.debit_amount', 'numeric', 'min:0'],
            'lines.*.description'     => ['nullable', 'string'],
            'lines.*.currency'        => ['nullable', 'string', 'size:3'],
            'lines.*.exchange_rate'   => ['nullable', 'numeric', 'min:0'],
            'lines.*.sort_order'      => ['nullable', 'integer'],
            'lines.*.metadata'        => ['nullable', 'array'],
        ];
    }
}
