<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'vehicle_id'        => $this->vehicle_id,
            'document_type'     => $this->document_type,
            'document_number'   => $this->document_number,
            'issuing_authority' => $this->issuing_authority,
            'issue_date'        => $this->issue_date,
            'expiry_date'       => $this->expiry_date,
            'file_path'         => $this->file_path,
            'notes'             => $this->notes,
            'is_active'         => $this->is_active,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
