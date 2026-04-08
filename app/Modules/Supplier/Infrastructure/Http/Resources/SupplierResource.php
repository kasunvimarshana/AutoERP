<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Resources\BaseResource;

final class SupplierResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'name'          => $this->name,
            'code'          => $this->code,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'tax_number'    => $this->tax_number,
            'currency'      => $this->currency,
            'payment_terms' => $this->payment_terms,
            'credit_limit'  => $this->credit_limit,
            'address'       => $this->address,
            'bank_details'  => $this->bank_details,
            'status'        => $this->status,
            'notes'         => $this->notes,
            'metadata'      => $this->metadata,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
