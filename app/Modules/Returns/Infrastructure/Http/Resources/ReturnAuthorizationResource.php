<?php

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Returns\Domain\Entities\ReturnAuthorization;

class ReturnAuthorizationResource extends JsonResource
{
    public function __construct(private readonly ReturnAuthorization $rma)
    {
        parent::__construct($rma);
    }

    public function toArray($request): array
    {
        return [
            'id'              => $this->rma->id,
            'tenant_id'       => $this->rma->tenantId,
            'rma_number'      => $this->rma->rmaNumber,
            'stock_return_id' => $this->rma->stockReturnId,
            'status'          => $this->rma->status,
            'expires_at'      => $this->rma->expiresAt?->format('Y-m-d\TH:i:s\Z'),
            'approved_by'     => $this->rma->approvedBy,
            'approved_at'     => $this->rma->approvedAt?->format('Y-m-d\TH:i:s\Z'),
            'notes'           => $this->rma->notes,
        ];
    }
}
