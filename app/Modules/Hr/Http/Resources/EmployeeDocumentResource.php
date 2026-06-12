<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class EmployeeDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->getKey(), 'document_type' => $this->document_type instanceof BackedEnum ? $this->document_type->value : $this->document_type, 'document_number' => $this->document_number, 'issued_date' => $this->issued_date?->toDateString(), 'expiry_date' => $this->expiry_date?->toDateString(), 'file_path' => $this->file_path, 'status' => $this->status instanceof BackedEnum ? $this->status->value : $this->status, 'notes' => $this->notes];
    }
}
