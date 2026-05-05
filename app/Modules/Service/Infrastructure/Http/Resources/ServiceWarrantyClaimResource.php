<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Service\Domain\Entities\ServiceWarrantyClaim;

class ServiceWarrantyClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ServiceWarrantyClaim $claim */
        $claim = $this->resource;

        return [
            'id' => $claim->getId(),
            'tenant_id' => $claim->getTenantId(),
            'org_unit_id' => $claim->getOrgUnitId(),
            'service_work_order_id' => $claim->getServiceWorkOrderId(),
            'supplier_id' => $claim->getSupplierId(),
            'warranty_provider' => $claim->getWarrantyProvider(),
            'claim_number' => $claim->getClaimNumber(),
            'status' => $claim->getStatus(),
            'currency_id' => $claim->getCurrencyId(),
            'claim_amount' => $claim->getClaimAmount(),
            'approved_amount' => $claim->getApprovedAmount(),
            'received_amount' => $claim->getReceivedAmount(),
            'submitted_at' => $claim->getSubmittedAt(),
            'resolved_at' => $claim->getResolvedAt(),
            'journal_entry_id' => $claim->getJournalEntryId(),
            'notes' => $claim->getNotes(),
            'metadata' => $claim->getMetadata(),
            'row_version' => $claim->getRowVersion(),
            'created_at' => $claim->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $claim->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
