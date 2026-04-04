<?php
namespace Modules\Customer\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Customer\Domain\Entities\Customer;

class CustomerResource extends JsonResource
{
    public function __construct(private readonly Customer $customer)
    {
        parent::__construct($customer);
    }

    public function toArray($request): array
    {
        return [
            'id'           => $this->customer->id,
            'tenant_id'    => $this->customer->tenantId,
            'name'         => $this->customer->name,
            'code'         => $this->customer->code,
            'status'       => $this->customer->status,
            'email'        => $this->customer->email,
            'phone'        => $this->customer->phone,
            'tax_number'   => $this->customer->taxNumber,
            'currency'     => $this->customer->currency,
            'credit_limit' => $this->customer->creditLimit,
            'notes'        => $this->customer->notes,
        ];
    }
}
