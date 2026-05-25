<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Services;

use Modules\Core\Application\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Application\DTOs\CustomerRecordData;
use Modules\Customer\Application\Repositories\CustomerAddressRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerContactRepositoryInterface;
use Modules\Customer\Application\Repositories\CustomerVehicleRepositoryInterface;
use Modules\Customer\Domain\Exceptions\CustomerIntegrityException;
use Modules\Customer\Domain\Exceptions\CustomerRecordNotFoundException;
use Modules\Customer\Domain\Services\CustomerDomainService;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;

class CustomerService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly CustomerContactRepositoryInterface $contacts,
        private readonly CustomerAddressRepositoryInterface $addresses,
        private readonly CustomerVehicleRepositoryInterface $vehicles,
        private readonly CustomerDomainService $domain,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("customer.resources.{$key}");

        if (! is_array($definition)) {
            throw CustomerRecordNotFoundException::for('Customer resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);
        $repository = $this->repository($resource);
        $criteria = ['tenant_id' => $tenantId, ...$filters];

        return $perPage === null
            ? $repository->getWhere($criteria)
            : $repository->paginateWhere($criteria, $perPage);
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = method_exists($repository, 'findForTenantById')
            ? $repository->findForTenantById($tenantId, $id)
            : $repository->getWhere(['tenant_id' => $tenantId, 'id' => $id])->first();

        if ($record === null) {
            throw CustomerRecordNotFoundException::for($definition['label'] ?? $resource, $id);
        }

        return $record;
    }

    public function create(string $resource, CustomerRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);

        return $repository->transaction(function () use ($definition, $repository, $data): Model {
            $record = $repository->create($this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId));
            $this->syncExclusiveFlags($definition['key'], $record, $data->tenantId);

            return $this->reloadRecord($definition['key'], $data->tenantId, $record->getKey());
        });
    }

    public function update(string $resource, int|string $tenantId, int|string $id, CustomerRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(function () use ($definition, $repository, $record, $data, $tenantId): Model {
            $updated = $repository->update($record, [
                ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
                'row_version' => $this->domain->nextRowVersion($record),
            ]);
            $this->syncExclusiveFlags($definition['key'], $updated, $tenantId);

            return $this->reloadRecord($definition['key'], $tenantId, $updated->getKey());
        });
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);

        return $repository->transaction(fn (): bool => $repository->delete($record));
    }

    public function setPrimaryContact(int|string $tenantId, int|string $id): Model
    {
        $contact = $this->find('customer_contacts', $tenantId, $id);

        return $this->contacts->transaction(function () use ($contact, $tenantId): Model {
            $updated = $this->contacts->update($contact, [
                'is_primary' => true,
                'row_version' => $this->domain->nextRowVersion($contact),
            ]);
            $this->syncExclusiveFlags('customer_contacts', $updated, $tenantId);

            return $this->reloadRecord('customer_contacts', $tenantId, $updated->getKey());
        });
    }

    public function setDefaultAddress(int|string $tenantId, int|string $id): Model
    {
        $address = $this->find('customer_addresses', $tenantId, $id);

        return $this->addresses->transaction(function () use ($address, $tenantId): Model {
            $updated = $this->addresses->update($address, [
                'is_default' => true,
                'row_version' => $this->domain->nextRowVersion($address),
            ]);
            $this->syncExclusiveFlags('customer_addresses', $updated, $tenantId);

            return $this->reloadRecord('customer_addresses', $tenantId, $updated->getKey());
        });
    }

    public function setCurrentVehicle(int|string $tenantId, int|string $id): Model
    {
        $vehicle = $this->find('customer_vehicles', $tenantId, $id);

        return $this->vehicles->transaction(function () use ($vehicle, $tenantId): Model {
            $updated = $this->vehicles->update($vehicle, [
                'is_current' => true,
                'row_version' => $this->domain->nextRowVersion($vehicle),
            ]);
            $this->syncExclusiveFlags('customer_vehicles', $updated, $tenantId);

            return $this->reloadRecord('customer_vehicles', $tenantId, $updated->getKey());
        });
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw CustomerRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw CustomerIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        unset($attributes['row_version']);

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'customers' => $this->prepareCustomerAttributes($attributes),
            'customer_contacts' => $this->prepareContactAttributes($attributes, $tenantId),
            'customer_addresses' => $this->prepareAddressAttributes($attributes, $tenantId),
            'customer_vehicles' => $this->prepareVehicleAttributes($attributes, $tenantId),
            default => $attributes,
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeScalars(array $attributes): array
    {
        foreach ($attributes as $key => $value) {
            if (is_string($value)) {
                $attributes[$key] = $this->domain->normalizeText($value);
            }
        }

        foreach (['credit_limit', 'geo_lat', 'geo_lng'] as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareCustomerAttributes(array $attributes): array
    {
        $attributes['type'] = $this->domain->normalizeEnum('customer type', $attributes['type'] ?? null, config('customer.customer_types', []), config('customer.defaults.customer_type'));
        $attributes['status'] = $this->domain->normalizeEnum('customer status', $attributes['status'] ?? null, config('customer.customer_statuses', []), config('customer.defaults.customer_status'), true);
        $attributes['payment_terms_days'] = $attributes['payment_terms_days'] ?? config('customer.defaults.payment_terms_days', 30);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareContactAttributes(array $attributes, int|string $tenantId): array
    {
        $this->domain->assertTenantCustomer($tenantId, $attributes['customer_id'] ?? null);
        $attributes['is_primary'] = $attributes['is_primary'] ?? false;

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAddressAttributes(array $attributes, int|string $tenantId): array
    {
        $this->domain->assertTenantCustomer($tenantId, $attributes['customer_id'] ?? null);
        $attributes['type'] = $this->domain->normalizeEnum('customer address type', $attributes['type'] ?? null, config('customer.address_types', []), config('customer.defaults.address_type'));
        $attributes['is_default'] = $attributes['is_default'] ?? false;

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareVehicleAttributes(array $attributes, int|string $tenantId): array
    {
        $this->domain->assertTenantCustomer($tenantId, $attributes['customer_id'] ?? null);
        $attributes['is_current'] = $attributes['is_current'] ?? false;
        $attributes['is_active'] = $attributes['is_active'] ?? true;

        return $attributes;
    }

    private function syncExclusiveFlags(string $resource, Model $record, int|string $tenantId): void
    {
        match ($resource) {
            'customer_contacts' => $this->unsetSiblingFlag($this->contacts, $record, $tenantId, 'is_primary', ['customer_id' => $record->customer_id]),
            'customer_addresses' => $this->unsetSiblingFlag($this->addresses, $record, $tenantId, 'is_default', ['customer_id' => $record->customer_id]),
            'customer_vehicles' => $this->unsetSiblingFlag($this->vehicles, $record, $tenantId, 'is_current', ['vehicle_id' => $record->vehicle_id]),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $scope
     */
    private function unsetSiblingFlag(BaseRepositoryInterface $repository, Model $record, int|string $tenantId, string $flag, array $scope): void
    {
        if (! (bool) $record->{$flag}) {
            return;
        }

        $siblings = $repository->getWhere(['tenant_id' => $tenantId, ...$scope]);

        foreach ($siblings as $sibling) {
            if ((string) $sibling->getKey() === (string) $record->getKey() || ! (bool) $sibling->{$flag}) {
                continue;
            }

            $repository->update($sibling, [
                $flag => false,
                'row_version' => $this->domain->nextRowVersion($sibling),
            ]);
        }
    }

    private function reloadRecord(string $resource, int|string $tenantId, int|string $id): Model
    {
        return $this->find($resource, $tenantId, $id);
    }
}

