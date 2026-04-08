<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class CustomerResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id'                      => $this->id,
            'code'                    => $this->code,
            'name'                    => $this->name,
            'type'                    => $this->type,
            'email'                   => $this->email,
            'phone'                   => $this->phone,
            'mobile'                  => $this->mobile,
            'fax'                     => $this->fax,
            'website'                 => $this->website,
            'tax_number'              => $this->tax_number,
            'registration_number'     => $this->registration_number,
            'currency_code'           => $this->currency_code,
            'credit_limit'            => $this->credit_limit,
            'balance'                 => $this->balance,
            'payment_terms_days'      => $this->payment_terms_days,
            'status'                  => $this->status,
            'billing_address_line1'   => $this->billing_address_line1,
            'billing_address_line2'   => $this->billing_address_line2,
            'billing_city'            => $this->billing_city,
            'billing_state'           => $this->billing_state,
            'billing_postal_code'     => $this->billing_postal_code,
            'billing_country'         => $this->billing_country,
            'shipping_address_line1'  => $this->shipping_address_line1,
            'shipping_address_line2'  => $this->shipping_address_line2,
            'shipping_city'           => $this->shipping_city,
            'shipping_state'          => $this->shipping_state,
            'shipping_postal_code'    => $this->shipping_postal_code,
            'shipping_country'        => $this->shipping_country,
            'notes'                   => $this->notes,
            'metadata'                => $this->metadata,
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
        ];
    }
}
