<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

/**
 * @OA\Schema(
 *   schema="JournalEntryLineResource",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="journal_entry_id", type="integer"),
 *   @OA\Property(property="account_id", type="integer"),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="debit_amount", type="number"),
 *   @OA\Property(property="credit_amount", type="number"),
 *   @OA\Property(property="currency", type="string"),
 *   @OA\Property(property="exchange_rate", type="number"),
 *   @OA\Property(property="sort_order", type="integer"),
 * )
 */
final class JournalEntryLineResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'journal_entry_id' => $this->journal_entry_id,
            'account_id'       => $this->account_id,
            'description'      => $this->description,
            'debit_amount'     => (float) $this->debit_amount,
            'credit_amount'    => (float) $this->credit_amount,
            'currency'         => $this->currency,
            'exchange_rate'    => (float) $this->exchange_rate,
            'sort_order'       => $this->sort_order,
            'metadata'         => $this->metadata,
            'account'          => $this->when(
                $this->relationLoaded('account'),
                fn () => new AccountResource($this->account)
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
