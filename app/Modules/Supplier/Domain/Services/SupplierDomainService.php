<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Domain\Exceptions\SupplierIntegrityException;
use Modules\Supplier\Domain\Exceptions\SupplierRecordNotFoundException;

class SupplierDomainService
{
    public function __construct(private readonly SupplierRepositoryInterface $suppliers) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (strtolower(trim($resource))) {
            'contacts', 'supplier-contacts' => 'supplier_contacts',
            'addresses', 'supplier-addresses' => 'supplier_addresses',
            'vehicles', 'supplier-vehicles' => 'supplier_vehicles',
            'items', 'supplier-items' => 'supplier_items',
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
        return number_format((float) ($value ?? 0), (int) config('supplier.precision.scale', 4), '.', '');
    }

    /**
     * @param  array<int, string>  $allowed
     */
    public function normalizeEnum(string $family, mixed $value, array $allowed, mixed $default = null): mixed
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw SupplierIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw SupplierIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
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
        $immutable = config("supplier.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw SupplierIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw SupplierIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantSupplier(int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $supplier = $this->suppliers->findForTenantById($tenantId, $id);

        if ($supplier === null) {
            throw SupplierRecordNotFoundException::for('Supplier', $id);
        }

        return $supplier;
    }
}
