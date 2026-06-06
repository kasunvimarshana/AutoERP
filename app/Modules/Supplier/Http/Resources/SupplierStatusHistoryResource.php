<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Supplier\Http\Resources\Concerns\FormatsSupplierResources;

final class SupplierStatusHistoryResource extends JsonResource
{
    use FormatsSupplierResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'old_status' => $this->enumValue($this->old_status),
            'new_status' => $this->enumValue($this->new_status),
            'reason' => $this->reason,
            'changed_at' => $this->changed_at?->toISOString(),
        ];
    }
}
