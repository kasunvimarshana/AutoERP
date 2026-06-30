<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class FinanceAccountAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'organization_unit_id' => $this->organization_unit_id !== null ? (int) $this->organization_unit_id : null,
            'account_role_id' => (int) $this->account_role_id,
            'account_id' => (int) $this->account_id,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'is_active' => (bool) $this->is_active,
            'role' => $this->whenLoaded('role', fn (): array => [
                'id' => (int) $this->role->getKey(),
                'code' => (string) $this->role->code,
                'name' => (string) $this->role->name,
            ]),
            'account' => $this->whenLoaded('account', fn (): array => [
                'id' => (int) $this->account->getKey(),
                'code' => (string) $this->account->code,
                'name' => (string) $this->account->name,
            ]),
        ];
    }
}
