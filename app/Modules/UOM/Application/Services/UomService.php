<?php

declare(strict_types=1);

namespace Modules\UOM\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\UOM\Application\DTOs\UnitOfMeasureData;
use Modules\UOM\Application\DTOs\UomConversionData;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Domain\Exceptions\UomRecordNotFoundException;
use Modules\UOM\Domain\Services\UomDomainService;

class UomService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly UnitOfMeasureRepositoryInterface $units,
        private readonly UomConversionRepositoryInterface $conversions,
        private readonly UomDomainService $domain,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listUnits(int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);
        $criteria = ['tenant_id' => $tenantId, ...$filters];

        return $perPage === null
            ? $this->units->getWhere($criteria)
            : $this->units->paginateWhere($criteria, $perPage);
    }

    public function findUnit(int|string $tenantId, int|string $id): Model
    {
        $record = $this->units->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw UomRecordNotFoundException::for('Unit of measure', $id);
        }

        return $record;
    }

    public function createUnit(UnitOfMeasureData $data): Model
    {
        $this->ensureTenantExists($data->tenantId);

        return $this->units->transaction(fn (): Model => $this->units->create($this->unitAttributes($data)));
    }

    public function updateUnit(int|string $tenantId, int|string $id, UnitOfMeasureData $data): Model
    {
        $record = $this->findUnit($tenantId, $id);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $this->units->transaction(fn (): Model => $this->units->update($record, [
            ...$this->unitAttributes($data),
            'row_version' => $this->domain->nextRowVersion($record),
        ]));
    }

    public function deleteUnit(int|string $tenantId, int|string $id): bool
    {
        return $this->units->transaction(fn (): bool => $this->units->delete($this->findUnit($tenantId, $id)));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listConversions(int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);
        $criteria = ['tenant_id' => $tenantId, ...$filters];

        return $perPage === null
            ? $this->conversions->getWhere($criteria, ['fromUom', 'toUom'])
            : $this->conversions->paginateWhere($criteria, $perPage, ['fromUom', 'toUom']);
    }

    public function findConversion(int|string $tenantId, int|string $id): Model
    {
        $record = $this->conversions->findForTenantById($tenantId, $id, ['fromUom', 'toUom']);

        if ($record === null) {
            throw UomRecordNotFoundException::for('UOM conversion', $id);
        }

        return $record;
    }

    public function createConversion(UomConversionData $data): Model
    {
        $this->ensureTenantExists($data->tenantId);
        $this->assertValidConversion($data);
        $this->domain->assertUniqueConversion($data->tenantId, $data->fromUomId, $data->toUomId, $data->itemId);

        return $this->conversions->transaction(fn (): Model => $this->conversions->create($this->conversionAttributes($data)));
    }

    public function updateConversion(int|string $tenantId, int|string $id, UomConversionData $data): Model
    {
        $record = $this->findConversion($tenantId, $id);
        $this->domain->assertRowVersion($data->rowVersion, $record);
        $this->assertValidConversion($data);
        $this->domain->assertUniqueConversion($data->tenantId, $data->fromUomId, $data->toUomId, $data->itemId, $id);

        return $this->conversions->transaction(fn (): Model => $this->conversions->update($record, [
            ...$this->conversionAttributes($data),
            'row_version' => $this->domain->nextRowVersion($record),
        ]));
    }

    public function deleteConversion(int|string $tenantId, int|string $id): bool
    {
        return $this->conversions->transaction(fn (): bool => $this->conversions->delete($this->findConversion($tenantId, $id)));
    }

    public function convert(
        int|string $tenantId,
        int|string $fromUomId,
        int|string $toUomId,
        string|int|float $quantity,
        int|string|null $itemId = null
    ): string {
        $this->ensureTenantExists($tenantId);
        $this->domain->assertSameTenantUnit($tenantId, $fromUomId, 'From UOM');
        $this->domain->assertSameTenantUnit($tenantId, $toUomId, 'To UOM');

        return $this->domain->convert($tenantId, $fromUomId, $toUomId, $quantity, $itemId);
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw UomRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function assertValidConversion(UomConversionData $data): void
    {
        $this->domain->assertDifferentUnits($data->fromUomId, $data->toUomId);
        $this->domain->assertSameTenantUnit($data->tenantId, $data->fromUomId, 'From UOM');
        $this->domain->assertSameTenantUnit($data->tenantId, $data->toUomId, 'To UOM');
    }

    /**
     * @return array<string, mixed>
     */
    private function unitAttributes(UnitOfMeasureData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'name' => $this->domain->normalizeText($data->name),
            'symbol' => $this->domain->normalizeSymbol($data->symbol),
            'type' => $this->domain->normalizeType($data->type),
            'is_base' => $data->isBase,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function conversionAttributes(UomConversionData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'from_uom_id' => $data->fromUomId,
            'to_uom_id' => $data->toUomId,
            'factor' => $this->domain->normalizeFactor($data->factor),
            'item_id' => $data->itemId,
            'is_bidirectional' => $data->isBidirectional,
            'is_active' => $data->isActive,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
