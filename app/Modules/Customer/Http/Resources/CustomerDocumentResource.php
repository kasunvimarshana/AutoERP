<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Http\Resources\Concerns\FormatsCustomerResources;

final class CustomerDocumentResource extends JsonResource
{
    use FormatsCustomerResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'document_type' => $this->enumValue($this->document_type),
            'document_number' => $this->document_number,
            'issued_date' => $this->issued_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'status' => $this->enumValue($this->status),
            'notes' => $this->notes,
        ];
    }
}
