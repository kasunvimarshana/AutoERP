<?php
declare(strict_types=1);
namespace Modules\Accounting\Infrastructure\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;
class AccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->resource->id,
            'tenant_id'         => $this->resource->tenantId,
            'parent_id'         => $this->resource->parentId,
            'code'              => $this->resource->code,
            'name'              => $this->resource->name,
            'type'              => $this->resource->type,
            'sub_type'          => $this->resource->subType,
            'normal_balance'    => $this->resource->normalBalance,
            'currency_code'     => $this->resource->currencyCode,
            'is_active'         => $this->resource->isActive,
            'is_locked'         => $this->resource->isLocked,
            'is_system_account' => $this->resource->isSystemAccount,
            'description'       => $this->resource->description,
            'path'              => $this->resource->path,
            'level'             => $this->resource->level,
            'created_at'        => $this->resource->createdAt,
            'updated_at'        => $this->resource->updatedAt,
        ];
    }
}
