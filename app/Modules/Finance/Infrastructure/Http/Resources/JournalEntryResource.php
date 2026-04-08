<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

/**
 * @OA\Schema(
 *   schema="JournalEntryResource",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="uuid", type="string", format="uuid"),
 *   @OA\Property(property="tenant_id", type="integer"),
 *   @OA\Property(property="reference_number", type="string"),
 *   @OA\Property(property="entry_date", type="string", format="date"),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="status", type="string", enum={"draft","posted","voided"}),
 *   @OA\Property(property="total_debit", type="number"),
 *   @OA\Property(property="total_credit", type="number"),
 *   @OA\Property(property="currency", type="string"),
 *   @OA\Property(property="posted_at", type="string", format="datetime", nullable=true),
 * )
 */
final class JournalEntryResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'tenant_id'        => $this->tenant_id,
            'reference_number' => $this->reference_number,
            'entry_date'       => $this->entry_date,
            'description'      => $this->description,
            'status'           => $this->status,
            'posted_at'        => $this->posted_at?->toIso8601String(),
            'posted_by'        => $this->posted_by,
            'voided_at'        => $this->voided_at?->toIso8601String(),
            'voided_by'        => $this->voided_by,
            'void_reason'      => $this->void_reason,
            'total_debit'      => (float) $this->total_debit,
            'total_credit'     => (float) $this->total_credit,
            'currency'         => $this->currency,
            'source_type'      => $this->source_type,
            'source_id'        => $this->source_id,
            'metadata'         => $this->metadata,
            'lines'            => $this->when(
                $this->relationLoaded('lines'),
                JournalEntryLineResource::collection($this->lines)
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
