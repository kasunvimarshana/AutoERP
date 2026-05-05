<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\PartyManagement\Domain\Entities\Party;

class PartyResource extends JsonResource
{
    public function __construct(private readonly Party $party)
    {
        parent::__construct($party);
    }

    public function toArray($request): array
    {
        return [
            'id'             => $this->party->getId(),
            'tenant_id'      => $this->party->getTenantId(),
            'party_type'     => $this->party->getPartyType(),
            'name'           => $this->party->getName(),
            'tax_number'     => $this->party->getTaxNumber(),
            'email'          => $this->party->getEmail(),
            'phone'          => $this->party->getPhone(),
            'address_line_1' => $this->party->getAddressLine1(),
            'address_line_2' => $this->party->getAddressLine2(),
            'city'           => $this->party->getCity(),
            'state_province' => $this->party->getStateProvince(),
            'postal_code'    => $this->party->getPostalCode(),
            'country_code'   => $this->party->getCountryCode(),
            'is_active'      => $this->party->isActive(),
            'notes'          => $this->party->getNotes(),
        ];
    }
}
