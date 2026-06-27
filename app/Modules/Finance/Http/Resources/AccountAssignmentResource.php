<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AccountAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'organization_unit_id' => $this->organization_unit_id,
            'role' => AccountRoleResource::make($this->whenLoaded('role')),
            'account' => FinanceAccountSummaryResource::make($this->whenLoaded('account')),
            'context_type' => $this->context_type,
            'context_id' => $this->context_id,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'is_active' => (bool) $this->is_active,
            'description' => $this->description,
        ];
    }
}
