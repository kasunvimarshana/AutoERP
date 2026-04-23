<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HR\Domain\Entities\PayslipLine;

class PayslipLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PayslipLine $entity */
        $entity = $this->resource;

        return [
            'id' => $entity->getId(),
            'payslip_id' => $entity->getPayslipId(),
            'payroll_item_id' => $entity->getPayrollItemId(),
            'item_name' => $entity->getItemName(),
            'item_code' => $entity->getItemCode(),
            'type' => $entity->getType(),
            'amount' => $entity->getAmount(),
            'metadata' => $entity->getMetadata(),
        ];
    }
}
