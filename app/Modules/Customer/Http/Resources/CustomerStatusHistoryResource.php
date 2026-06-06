<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Http\Resources\Concerns\FormatsCustomerResources;

final class CustomerStatusHistoryResource extends JsonResource
{
    use FormatsCustomerResources;

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
