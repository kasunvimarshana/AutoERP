<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class BankAccountResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'account_id'      => $this->account_id,
            'name'            => $this->name,
            'account_number'  => $this->account_number,
            'routing_number'  => $this->routing_number,
            'bank_name'       => $this->bank_name,
            'bank_code'       => $this->bank_code,
            'account_type'    => $this->account_type,
            'currency_code'   => $this->currency_code,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'credit_limit'    => $this->credit_limit,
            'status'          => $this->status,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
