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

final class HrDepartmentResource extends HrMasterResource
{
    public function toArray(Request $request): array
    {
        return [...parent::toArray($request), 'parent_id' => $this->parent_id, 'parent' => $this->whenLoaded('parent', fn () => $this->parent ? new self($this->parent) : null)];
    }
}
