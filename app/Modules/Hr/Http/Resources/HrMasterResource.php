<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class HrMasterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->getKey(), 'row_version' => (int) ($this->row_version ?? 1), 'tenant_id' => $this->tenant_id, 'organization_unit_id' => $this->organization_unit_id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'is_active' => (bool) $this->is_active, 'sort_order' => $this->sort_order ?? null];
    }
}
