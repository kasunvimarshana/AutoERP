<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'asset_id' => $this->resource->getAssetId(),
            'document_type' => $this->resource->getDocumentType(),
            'document_number' => $this->resource->getDocumentNumber(),
            'issue_date' => $this->resource->getIssueDate()?->format('Y-m-d'),
            'expiry_date' => $this->resource->getExpiryDate()?->format('Y-m-d'),
            'issuing_authority' => $this->resource->getIssuingAuthority(),
            'file_url' => $this->resource->getFileUrl(),
        ];
    }
}
