<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Supplier\Http\Resources\Concerns\FormatsSupplierResources;

final class SupplierBankAccountResource extends JsonResource
{
    use FormatsSupplierResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'bank_name' => $this->bank_name,
            'branch_name' => $this->branch_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'swift_code' => $this->swift_code,
            'iban' => $this->iban,
            'currency' => $this->relationLoaded('currency') ? $this->namedResource($this->currency, true) : null,
            'is_primary' => (bool) $this->is_primary,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
        ];
    }
}
