<?php

declare(strict_types=1);

namespace Modules\PartyManagement\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PartyManagement\Application\Contracts\ManagePartyServiceInterface;
use Modules\PartyManagement\Domain\Entities\Party;
use Modules\PartyManagement\Domain\RepositoryInterfaces\PartyRepositoryInterface;

class ManagePartyService implements ManagePartyServiceInterface
{
    public function __construct(
        private readonly PartyRepositoryInterface $parties,
    ) {}

    public function create(array $data): Party
    {
        return DB::transaction(function () use ($data): Party {
            $party = new Party(
                id: Str::uuid()->toString(),
                tenantId: (int) $data['tenant_id'],
                partyType: $data['party_type'],
                name: $data['name'],
                taxNumber: $data['tax_number'] ?? null,
                email: $data['email'] ?? null,
                phone: $data['phone'] ?? null,
                addressLine1: $data['address_line_1'] ?? null,
                addressLine2: $data['address_line_2'] ?? null,
                city: $data['city'] ?? null,
                stateProvince: $data['state_province'] ?? null,
                postalCode: $data['postal_code'] ?? null,
                countryCode: $data['country_code'] ?? null,
                isActive: (bool) ($data['is_active'] ?? true),
                notes: $data['notes'] ?? null,
            );

            return $this->parties->create($party);
        });
    }

    public function update(int $tenantId, string $id, array $data): Party
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Party {
            $party = $this->parties->findById($tenantId, $id);

            $party->update(
                name: $data['name'] ?? $party->getName(),
                taxNumber: array_key_exists('tax_number', $data) ? $data['tax_number'] : $party->getTaxNumber(),
                email: array_key_exists('email', $data) ? $data['email'] : $party->getEmail(),
                phone: array_key_exists('phone', $data) ? $data['phone'] : $party->getPhone(),
                addressLine1: array_key_exists('address_line_1', $data) ? $data['address_line_1'] : $party->getAddressLine1(),
                addressLine2: array_key_exists('address_line_2', $data) ? $data['address_line_2'] : $party->getAddressLine2(),
                city: array_key_exists('city', $data) ? $data['city'] : $party->getCity(),
                stateProvince: array_key_exists('state_province', $data) ? $data['state_province'] : $party->getStateProvince(),
                postalCode: array_key_exists('postal_code', $data) ? $data['postal_code'] : $party->getPostalCode(),
                countryCode: array_key_exists('country_code', $data) ? $data['country_code'] : $party->getCountryCode(),
                isActive: isset($data['is_active']) ? (bool) $data['is_active'] : $party->isActive(),
                notes: array_key_exists('notes', $data) ? $data['notes'] : $party->getNotes(),
            );

            return $this->parties->update($party);
        });
    }

    public function delete(int $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->parties->findById($tenantId, $id);
            $this->parties->delete($tenantId, $id);
        });
    }

    public function find(int $tenantId, string $id): Party
    {
        return $this->parties->findById($tenantId, $id);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->parties->getByTenant($tenantId, $perPage, $page);
    }
}
