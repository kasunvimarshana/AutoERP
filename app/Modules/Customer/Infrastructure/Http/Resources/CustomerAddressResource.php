<?php
namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Domain\Entities\CustomerAddress;

class CustomerAddressResource extends JsonResource
{
    public function __construct(private readonly CustomerAddress $address)
    {
        parent::__construct($address);
    }

    public function toArray($request): array
    {
        return [
            'id'           => $this->address->id,
            'customer_id'  => $this->address->customerId,
            'address_type' => $this->address->addressType,
            'street'       => $this->address->street,
            'city'         => $this->address->city,
            'state'        => $this->address->state,
            'country'      => $this->address->country,
            'postal_code'  => $this->address->postalCode,
            'is_default'   => $this->address->isDefault,
        ];
    }
}
