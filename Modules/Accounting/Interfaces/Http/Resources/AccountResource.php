<?php

declare(strict_types=1);

namespace Modules\Accounting\Interfaces\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Accounting\Domain\Entities\Account;

class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Account $account */
        $account = $this->resource;

        return [
            'id' => $account->id,
            'tenant_id' => $account->tenantId,
            'parent_id' => $account->parentId,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type->value,
            'status' => $account->status->value,
            'description' => $account->description,
            'is_system_account' => $account->isSystemAccount,
            'opening_balance' => $account->openingBalance,
            'current_balance' => $account->currentBalance,
            'created_at' => $account->createdAt,
            'updated_at' => $account->updatedAt,
        ];
    }
}
