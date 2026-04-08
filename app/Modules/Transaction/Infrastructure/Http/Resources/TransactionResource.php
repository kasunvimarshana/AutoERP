<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Transaction\Domain\Entities\Transaction;

class TransactionResource extends JsonResource
{
    /** @var Transaction */
    public $resource;

    public function toArray($request): array
    {
        return [
            'id'               => $this->resource->id,
            'tenant_id'        => $this->resource->tenantId,
            'type'             => $this->resource->type,
            'reference_type'   => $this->resource->referenceType,
            'reference_id'     => $this->resource->referenceId,
            'status'           => $this->resource->status,
            'description'      => $this->resource->description,
            'transaction_date' => $this->resource->transactionDate->format('Y-m-d'),
            'total_amount'     => $this->resource->totalAmount,
            'created_at'       => $this->resource->createdAt->format('Y-m-d H:i:s'),
            'updated_at'       => $this->resource->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
