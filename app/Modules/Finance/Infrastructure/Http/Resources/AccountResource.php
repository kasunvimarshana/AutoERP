<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

/**
 * @OA\Schema(
 *   schema="AccountResource",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="uuid", type="string", format="uuid"),
 *   @OA\Property(property="tenant_id", type="integer"),
 *   @OA\Property(property="parent_id", type="integer", nullable=true),
 *   @OA\Property(property="code", type="string"),
 *   @OA\Property(property="name", type="string"),
 *   @OA\Property(property="type", type="string"),
 *   @OA\Property(property="nature", type="string"),
 *   @OA\Property(property="classification", type="string", nullable=true),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="is_active", type="boolean"),
 *   @OA\Property(property="is_bank_account", type="boolean"),
 *   @OA\Property(property="is_system", type="boolean"),
 *   @OA\Property(property="currency", type="string"),
 *   @OA\Property(property="opening_balance", type="number"),
 *   @OA\Property(property="current_balance", type="number"),
 * )
 */
final class AccountResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'tenant_id'           => $this->tenant_id,
            'parent_id'           => $this->parent_id,
            'code'                => $this->code,
            'name'                => $this->name,
            'type'                => $this->type,
            'nature'              => $this->nature,
            'classification'      => $this->classification,
            'description'         => $this->description,
            'is_active'           => $this->is_active,
            'is_bank_account'     => $this->is_bank_account,
            'is_system'           => $this->is_system,
            'bank_name'           => $this->bank_name,
            'bank_account_number' => $this->when($this->is_bank_account, $this->bank_account_number),
            'bank_routing_number' => $this->when($this->is_bank_account, $this->bank_routing_number),
            'currency'            => $this->currency,
            'opening_balance'     => (float) $this->opening_balance,
            'current_balance'     => (float) $this->current_balance,
            'metadata'            => $this->metadata,
            'children'            => $this->when(
                $this->relationLoaded('children'),
                AccountResource::collection($this->children)
            ),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
