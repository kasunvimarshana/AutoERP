<?php

namespace Modules\Returns\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Returns\Domain\Entities\CreditMemo;

class CreditMemoResource extends JsonResource
{
    public function __construct(private readonly CreditMemo $memo)
    {
        parent::__construct($memo);
    }

    public function toArray($request): array
    {
        return [
            'id'              => $this->memo->id,
            'tenant_id'       => $this->memo->tenantId,
            'memo_number'     => $this->memo->memoNumber,
            'stock_return_id' => $this->memo->stockReturnId,
            'amount'          => $this->memo->amount,
            'status'          => $this->memo->status,
            'customer_id'     => $this->memo->customerId,
            'currency'        => $this->memo->currency,
            'notes'           => $this->memo->notes,
            'issued_at'       => $this->memo->issuedAt?->format('Y-m-d\TH:i:s\Z'),
            'issued_by'       => $this->memo->issuedBy,
        ];
    }
}
