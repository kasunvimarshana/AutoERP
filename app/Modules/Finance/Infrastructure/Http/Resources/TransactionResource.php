<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

/**
 * @OA\Schema(
 *   schema="TransactionResource",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="uuid", type="string", format="uuid"),
 *   @OA\Property(property="tenant_id", type="integer"),
 *   @OA\Property(property="reference_number", type="string"),
 *   @OA\Property(property="type", type="string"),
 *   @OA\Property(property="status", type="string"),
 *   @OA\Property(property="transaction_date", type="string", format="date"),
 *   @OA\Property(property="amount", type="number"),
 *   @OA\Property(property="currency", type="string"),
 *   @OA\Property(property="exchange_rate", type="number"),
 *   @OA\Property(property="description", type="string", nullable=true),
 * )
 */
final class TransactionResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'tenant_id'        => $this->tenant_id,
            'journal_entry_id' => $this->journal_entry_id,
            'reference_number' => $this->reference_number,
            'type'             => $this->type,
            'status'           => $this->status,
            'transaction_date' => $this->transaction_date,
            'amount'           => (float) $this->amount,
            'currency'         => $this->currency,
            'exchange_rate'    => (float) $this->exchange_rate,
            'from_account_id'  => $this->from_account_id,
            'to_account_id'    => $this->to_account_id,
            'description'      => $this->description,
            'category'         => $this->category,
            'tags'             => $this->tags,
            'contact_type'     => $this->contact_type,
            'contact_id'       => $this->contact_id,
            'attachments'      => $this->attachments,
            'metadata'         => $this->metadata,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
