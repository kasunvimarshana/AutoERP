<?php

declare(strict_types=1);

namespace Modules\Supplier\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Supplier\Http\Resources\Concerns\FormatsSupplierResources;

final class SupplierDocumentResource extends JsonResource
{
    use FormatsSupplierResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'document_type' => $this->enumValue($this->document_type),
            'document_number' => $this->document_number,
            'issued_date' => $this->issued_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'file_path' => $this->file_path,
            'status' => $this->enumValue($this->status),
            'notes' => $this->notes,
        ];
    }
}
