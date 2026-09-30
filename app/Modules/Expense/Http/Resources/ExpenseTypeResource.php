<?php

declare(strict_types=1);

namespace Modules\Expense\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ExpenseTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
