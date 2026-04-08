<?php

declare(strict_types=1);

namespace Modules\Transaction\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Transaction\Domain\Entities\TransactionLine;

class TransactionLineResource extends JsonResource
{
    /** @var TransactionLine */
    public $resource;

    public function toArray($request): array
    {
        return [
            'id'             => $this->resource->id,
            'tenant_id'      => $this->resource->tenantId,
            'transaction_id' => $this->resource->transactionId,
            'account_id'     => $this->resource->accountId,
            'product_id'     => $this->resource->productId,
            'quantity'       => $this->resource->quantity,
            'unit_price'     => $this->resource->unitPrice,
            'amount'         => $this->resource->amount,
            'debit'          => $this->resource->debit,
            'credit'         => $this->resource->credit,
            'notes'          => $this->resource->notes,
            'created_at'     => $this->resource->createdAt->format('Y-m-d H:i:s'),
            'updated_at'     => $this->resource->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
