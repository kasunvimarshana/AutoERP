<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Service\Domain\Entities\ServiceReturn;

class ServiceReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var ServiceReturn $return */
        $return = $this->resource;

        return [
            'id' => $return->getId(),
            'tenant_id' => $return->getTenantId(),
            'org_unit_id' => $return->getOrgUnitId(),
            'service_work_order_id' => $return->getServiceWorkOrderId(),
            'return_number' => $return->getReturnNumber(),
            'return_type' => $return->getReturnType(),
            'status' => $return->getStatus(),
            'reason_code' => $return->getReasonCode(),
            'processed_by' => $return->getProcessedBy(),
            'processed_at' => $return->getProcessedAt(),
            'currency_id' => $return->getCurrencyId(),
            'total_amount' => $return->getTotalAmount(),
            'journal_entry_id' => $return->getJournalEntryId(),
            'payment_id' => $return->getPaymentId(),
            'notes' => $return->getNotes(),
            'metadata' => $return->getMetadata(),
            'row_version' => $return->getRowVersion(),
            'created_at' => $return->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $return->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
