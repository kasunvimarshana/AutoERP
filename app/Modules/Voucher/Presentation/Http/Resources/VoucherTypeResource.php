<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VoucherTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'tenant_id' => $this->resource['tenant_id'] ?? null,
            'organization_unit_id' => $this->resource['organization_unit_id'] ?? null,
            'name' => $this->resource['name'] ?? null,
            'code' => $this->resource['code'] ?? null,
            'direction' => $this->resource['direction'] ?? null,
            'posting_behavior' => $this->resource['posting_behavior'] ?? null,
            'document_type_id' => $this->resource['document_type_id'] ?? null,
            'document_definition_id' => $this->resource['document_definition_id'] ?? null,
            'requires_approval' => $this->resource['requires_approval'] ?? true,
            'allow_direct_posting' => $this->resource['allow_direct_posting'] ?? false,
            'allow_reversal' => $this->resource['allow_reversal'] ?? true,
            'allow_partial_allocation' => $this->resource['allow_partial_allocation'] ?? true,
            'is_active' => $this->resource['is_active'] ?? true,
            'allowed_payment_methods' => $this->resource['allowed_payment_methods'] ?? [],
            'status_workflow' => $this->resource['status_workflow'] ?? [],
            'metadata' => $this->resource['metadata'] ?? [],
            'created_at' => $this->resource['created_at'] ?? null,
            'updated_at' => $this->resource['updated_at'] ?? null,
        ];
    }
}
