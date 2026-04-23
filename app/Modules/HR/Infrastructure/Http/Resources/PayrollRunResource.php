<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\PayrollRun;

class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PayrollRun $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'tenant_id' => $entity->getTenantId(),
            'period_start' => $entity->getPeriodStart()->format('Y-m-d'),
            'period_end' => $entity->getPeriodEnd()->format('Y-m-d'),
            'status' => $entity->getStatus()->value,
            'processed_at' => $entity->getProcessedAt()?->format('c'),
            'approved_at' => $entity->getApprovedAt()?->format('c'),
            'approved_by' => $entity->getApprovedBy(),
            'total_gross' => $entity->getTotalGross(),
            'total_deductions' => $entity->getTotalDeductions(),
            'total_net' => $entity->getTotalNet(),
            'metadata' => $entity->getMetadata(),
            'created_at' => $entity->getCreatedAt()->format('c'),
            'updated_at' => $entity->getUpdatedAt()->format('c'),
        ];
    }
}
