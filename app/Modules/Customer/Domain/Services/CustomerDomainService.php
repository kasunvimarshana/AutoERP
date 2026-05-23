<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Customer\Application\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Domain\Exceptions\CustomerIntegrityException;
use Modules\Customer\Domain\Exceptions\CustomerRecordNotFoundException;

class CustomerDomainService
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (strtolower(trim($resource))) {
            'contacts', 'customer-contacts' => 'customer_contacts',
            'addresses', 'customer-addresses' => 'customer_addresses',
            'vehicles', 'customer-vehicles' => 'customer_vehicles',
            default => str_replace('-', '_', strtolower(trim($resource))),
        };
    }

    public function normalizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>|null
     */
    public function normalizeMetadata(?array $metadata): ?array
    {
        return $metadata === [] ? null : $metadata;
    }

    public function normalizeDecimal(string|int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), (int) config('customer.precision.scale', 4), '.', '');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public function normalizeEnum(string $family, mixed $value, array $allowed, mixed $default = null, bool $uppercase = false): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = $uppercase ? strtoupper((string) $value) : strtolower((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw CustomerIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw CustomerIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
        }
    }

    public function nextRowVersion(Model $record): int
    {
        return ((int) $record->row_version) + 1;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function ensureMutable(string $resource, Model $record, array $definition, bool $updating = false): void
    {
        $immutable = config("customer.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw CustomerIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw CustomerIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantCustomer(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $customer = $this->customers->findForTenantById($tenantId, $id);

        if ($customer === null) {
            throw CustomerRecordNotFoundException::for('Customer', $id);
        }

        return $customer;
    }
}
