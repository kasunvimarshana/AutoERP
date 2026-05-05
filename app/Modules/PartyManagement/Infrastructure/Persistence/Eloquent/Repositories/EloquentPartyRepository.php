<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\PartyManagement\Domain\Entities\Party;
use Modules\PartyManagement\Domain\RepositoryInterfaces\PartyRepositoryInterface;
use Modules\PartyManagement\Infrastructure\Persistence\Eloquent\Models\PartyModel;
use RuntimeException;

class EloquentPartyRepository implements PartyRepositoryInterface
{
    public function create(Party $party): Party
    {
        PartyModel::create([
            'id'            => $party->getId(),
            'tenant_id'     => $party->getTenantId(),
            'party_type'    => $party->getPartyType(),
            'name'          => $party->getName(),
            'tax_number'    => $party->getTaxNumber(),
            'email'         => $party->getEmail(),
            'phone'         => $party->getPhone(),
            'address_line_1'=> $party->getAddressLine1(),
            'address_line_2'=> $party->getAddressLine2(),
            'city'          => $party->getCity(),
            'state_province'=> $party->getStateProvince(),
            'postal_code'   => $party->getPostalCode(),
            'country_code'  => $party->getCountryCode(),
            'is_active'     => $party->isActive(),
            'notes'         => $party->getNotes(),
        ]);

        return $this->findById($party->getTenantId(), $party->getId());
    }

    public function update(Party $party): Party
    {
        PartyModel::where('tenant_id', $party->getTenantId())
            ->where('id', $party->getId())
            ->update([
                'name'          => $party->getName(),
                'tax_number'    => $party->getTaxNumber(),
                'email'         => $party->getEmail(),
                'phone'         => $party->getPhone(),
                'address_line_1'=> $party->getAddressLine1(),
                'address_line_2'=> $party->getAddressLine2(),
                'city'          => $party->getCity(),
                'state_province'=> $party->getStateProvince(),
                'postal_code'   => $party->getPostalCode(),
                'country_code'  => $party->getCountryCode(),
                'is_active'     => $party->isActive(),
                'notes'         => $party->getNotes(),
            ]);

        return $this->findById($party->getTenantId(), $party->getId());
    }

    public function delete(int $tenantId, string $id): void
    {
        PartyModel::where('tenant_id', $tenantId)->where('id', $id)->delete();
    }

    public function findById(int $tenantId, string $id): Party
    {
        $model = PartyModel::where('tenant_id', $tenantId)->where('id', $id)->first();

        if ($model === null) {
            throw new RuntimeException("Party not found: {$id}");
        }

        return $this->toDomain($model);
    }

    public function getByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return PartyModel::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByTaxNumber(int $tenantId, string $taxNumber): ?Party
    {
        $model = PartyModel::where('tenant_id', $tenantId)
            ->where('tax_number', $taxNumber)
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    private function toDomain(PartyModel $model): Party
    {
        return new Party(
            id: $model->id,
            tenantId: $model->tenant_id,
            partyType: $model->party_type,
            name: $model->name,
            taxNumber: $model->tax_number,
            email: $model->email,
            phone: $model->phone,
            addressLine1: $model->address_line_1,
            addressLine2: $model->address_line_2,
            city: $model->city,
            stateProvince: $model->state_province,
            postalCode: $model->postal_code,
            countryCode: $model->country_code,
            isActive: (bool) $model->is_active,
            notes: $model->notes,
        );
    }
}
