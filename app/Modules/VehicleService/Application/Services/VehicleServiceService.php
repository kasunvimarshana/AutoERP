<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\Services;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\VehicleService\Application\Actions\DeleteVehicleServiceRecordAction;
use Modules\VehicleService\Application\Actions\FindVehicleServiceRecordAction;
use Modules\VehicleService\Application\Actions\ListVehicleServiceRecordsAction;
use Modules\VehicleService\Application\Actions\PersistVehicleServiceRecordAction;
use Modules\VehicleService\Application\DTOs\VehicleServiceRecordData;
use Modules\VehicleService\Application\Repositories\VehicleServiceDiagnosticLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceDiagnosticRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceInspectionRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardLineRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceJobCardRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborAssignmentRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceLaborItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceNonInventoryItemRepositoryInterface;
use Modules\VehicleService\Application\Repositories\VehicleServiceTypeRepositoryInterface;
use Modules\VehicleService\Domain\Exceptions\VehicleServiceIntegrityException;
use Modules\VehicleService\Domain\Exceptions\VehicleServiceRecordNotFoundException;
use Modules\VehicleService\Domain\Services\VehicleServiceDomainService;

class VehicleServiceService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly VehicleServiceTypeRepositoryInterface $serviceTypes,
        private readonly VehicleServiceJobCardRepositoryInterface $jobCards,
        private readonly VehicleServiceJobCardLineRepositoryInterface $jobCardLines,
        private readonly VehicleServiceLaborItemRepositoryInterface $laborItems,
        private readonly VehicleServiceNonInventoryItemRepositoryInterface $nonInventoryItems,
        private readonly VehicleServiceLaborAssignmentRepositoryInterface $laborAssignments,
        private readonly VehicleServiceDiagnosticRepositoryInterface $diagnostics,
        private readonly VehicleServiceDiagnosticLineRepositoryInterface $diagnosticLines,
        private readonly VehicleServiceInspectionRepositoryInterface $inspections,
        private readonly VehicleServiceInspectionLineRepositoryInterface $inspectionLines,
        private readonly VehicleServiceDomainService $domain,
        private readonly ListVehicleServiceRecordsAction $listRecords,
        private readonly FindVehicleServiceRecordAction $findRecord,
        private readonly PersistVehicleServiceRecordAction $persistRecord,
        private readonly DeleteVehicleServiceRecordAction $deleteRecord,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("vehicle-service.resources.{$key}");

        if (! is_array($definition)) {
            throw VehicleServiceRecordNotFoundException::for('Vehicle service resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);

        return $this->listRecords->execute(
            $this->repository($resource),
            ['tenant_id' => $tenantId, ...$filters],
            $perPage,
        );
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);

        return $this->findRecord->execute(
            $this->repository($resource),
            $definition['label'] ?? $resource,
            $tenantId,
            $id,
        );
    }

    public function create(string $resource, VehicleServiceRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $this->ensureTenantExists($data->tenantId);

        return $repository->transaction(function () use ($definition, $repository, $data): Model {
            $record = $this->persistRecord->create(
                $repository,
                $this->prepareAttributes($definition['key'], $data->attributes, $data->tenantId),
            );
            $this->recalculateForResourceChange($definition['key'], $record, $data->tenantId);

            return $this->reloadRecord($definition['key'], $data->tenantId, $record->getKey());
        });
    }

    public function update(string $resource, int|string $tenantId, int|string $id, VehicleServiceRecordData $data): Model
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);
        $this->domain->assertRowVersion($data->rowVersion, $record);

        return $repository->transaction(function () use ($definition, $repository, $record, $data, $tenantId): Model {
            $originalParent = $this->parentReference($definition['key'], $record);
            $updated = $this->persistRecord->update($repository, $record, [
                ...$this->prepareAttributes($definition['key'], $data->attributes, $tenantId),
                'row_version' => $this->domain->nextRowVersion($record),
            ]);
            $updatedParent = $this->parentReference($definition['key'], $updated);

            if (! $this->sameParentReference($originalParent, $updatedParent)) {
                $this->recalculateParentReference($tenantId, $originalParent);
            }

            $this->recalculateForResourceChange($definition['key'], $updated, $tenantId);

            return $this->reloadRecord($definition['key'], $tenantId, $updated->getKey());
        });
    }

    public function delete(string $resource, int|string $tenantId, int|string $id): bool
    {
        $definition = $this->definition($resource);
        $repository = $this->repository($resource);
        $record = $this->find($resource, $tenantId, $id);

        $this->domain->ensureMutable($definition['key'], $record, $definition, true);

        return $repository->transaction(function () use ($definition, $repository, $record, $tenantId): bool {
            $parent = $this->parentReference($definition['key'], $record);
            $deleted = $this->deleteRecord->execute($repository, $record);

            if ($deleted) {
                $this->recalculateParentReference($tenantId, $parent);
            }

            return $deleted;
        });
    }

    public function recalculateJobCard(int|string $tenantId, int|string $id): Model
    {
        $jobCard = $this->domain->assertTenantJobCard($tenantId, $id);

        return $this->jobCards->transaction(function () use ($tenantId, $jobCard): Model {
            $totals = [
                ...$this->domain->calculateJobCardTotals(
                    $this->jobCardLines->getWhere(['tenant_id' => $tenantId, 'job_card_id' => $jobCard->getKey()]),
                    $this->nonInventoryItems->getWhere(['tenant_id' => $tenantId, 'job_card_id' => $jobCard->getKey()]),
                    $this->laborItems->getWhere(['tenant_id' => $tenantId, 'job_card_id' => $jobCard->getKey()]),
                    $jobCard->getAttributes(),
                ),
                'row_version' => $this->domain->nextRowVersion($jobCard),
            ];

            return $this->jobCards->update($jobCard, $totals);
        });
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw VehicleServiceRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw VehicleServiceIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        foreach (config('vehicle-service.calculated_columns', []) as $calculatedColumn) {
            unset($attributes[$calculatedColumn]);
        }

        $attributes = [
            ...$this->normalizeScalars($attributes),
            'tenant_id' => $tenantId,
        ];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'service_types' => $this->prepareServiceTypeAttributes($attributes, $tenantId),
            'job_cards' => $this->prepareJobCardAttributes($attributes, $tenantId),
            'job_card_lines' => $this->prepareJobCardLineAttributes($attributes, $tenantId),
            'labor_items' => $this->prepareLaborItemAttributes($attributes, $tenantId),
            'non_inventory_items' => $this->prepareNonInventoryItemAttributes($attributes, $tenantId),
            'labor_assignments' => $this->prepareLaborAssignmentAttributes($attributes, $tenantId),
            'diagnostics' => $this->prepareDiagnosticAttributes($attributes, $tenantId),
            'diagnostic_lines' => $this->prepareDiagnosticLineAttributes($attributes, $tenantId),
            'inspections' => $this->prepareInspectionAttributes($attributes, $tenantId),
            'inspection_lines' => $this->prepareInspectionLineAttributes($attributes, $tenantId),
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

        foreach (config('vehicle-service.decimal_columns', []) as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        return $attributes;
    }

    private function prepareServiceTypeAttributes(array $attributes, int|string $tenantId): array
    {
        $parent = $this->domain->assertTenantServiceType($tenantId, $attributes['parent_id'] ?? null);

        if ($parent !== null) {
            $attributes['depth'] = ((int) $parent->depth) + 1;
            $attributes['path'] = trim((string) ($parent->path ?? $parent->getKey()).'/'.($attributes['code'] ?? $attributes['name'] ?? ''), '/');
        }

        $attributes['is_active'] = $attributes['is_active'] ?? true;

        return $attributes;
    }

    private function prepareJobCardAttributes(array $attributes, int|string $tenantId): array
    {
        $this->domain->assertTenantServiceType($tenantId, $attributes['service_type_id'] ?? null);
        $attributes['priority'] = $this->domain->normalizeEnum('priority', $attributes['priority'] ?? null, config('vehicle-service.priorities', []), config('vehicle-service.priorities.1', 'medium'));
        $attributes['status'] = $this->domain->normalizeEnum('job card status', $attributes['status'] ?? null, config('vehicle-service.job_card_statuses', []), config('vehicle-service.job_card_statuses.0', 'open'));
        $attributes['header_discount_type'] = $this->domain->normalizeEnum('discount_type', $attributes['header_discount_type'] ?? null, config('vehicle-service.discount_types', []), config('vehicle-service.discount_types.0', 'fixed'));
        $attributes['exchange_rate'] = $attributes['exchange_rate'] ?? $this->domain->normalizeDecimal(1);

        return $attributes;
    }

    private function prepareJobCardLineAttributes(array $attributes, int|string $tenantId): array
    {
        $jobCard = $this->requiredJobCard($tenantId, $attributes['job_card_id'] ?? null);
        $this->domain->ensureMutable('job_cards', $jobCard, $this->definition('job_cards'), true);

        return $this->domain->prepareChargeLineAmounts($attributes);
    }

    private function prepareLaborItemAttributes(array $attributes, int|string $tenantId): array
    {
        $jobCard = $this->requiredJobCard($tenantId, $attributes['job_card_id'] ?? null);
        $this->domain->ensureMutable('job_cards', $jobCard, $this->definition('job_cards'), true);

        return $this->domain->prepareChargeLineAmounts($attributes, true);
    }

    private function prepareNonInventoryItemAttributes(array $attributes, int|string $tenantId): array
    {
        $jobCard = $this->requiredJobCard($tenantId, $attributes['job_card_id'] ?? null);
        $this->domain->ensureMutable('job_cards', $jobCard, $this->definition('job_cards'), true);

        return $this->domain->prepareChargeLineAmounts($attributes);
    }

    private function prepareLaborAssignmentAttributes(array $attributes, int|string $tenantId): array
    {
        $jobCard = $this->requiredJobCard($tenantId, $attributes['job_card_id'] ?? null);
        $laborItem = $this->domain->assertTenantLaborItem($tenantId, $attributes['labor_item_id'] ?? null);
        $this->assertRequiredRecord($laborItem, 'Vehicle service labor item', $attributes['labor_item_id'] ?? null);
        $this->domain->assertSameJobCard($laborItem, $jobCard->getKey(), 'Labor assignment item must belong to the same job card.');
        $this->domain->ensureMutable('job_cards', $jobCard, $this->definition('job_cards'), true);

        return $this->domain->prepareLaborAssignmentAmounts($attributes);
    }

    private function prepareDiagnosticAttributes(array $attributes, int|string $tenantId): array
    {
        $this->requiredJobCard($tenantId, $attributes['job_card_id'] ?? null);
        $attributes['diagnostic_phase'] = $this->domain->normalizeEnum('diagnostic phase', $attributes['diagnostic_phase'] ?? null, config('vehicle-service.service_phases', []), null);
        $attributes['overall_result'] = $this->domain->normalizeEnum('diagnostic result', $attributes['overall_result'] ?? null, config('vehicle-service.overall_results', []), config('vehicle-service.overall_results.0', 'pass'));

        return $attributes;
    }

    private function prepareDiagnosticLineAttributes(array $attributes, int|string $tenantId): array
    {
        $diagnostic = $this->domain->assertTenantDiagnostic($tenantId, $attributes['diagnostic_id'] ?? null);
        $this->assertRequiredRecord($diagnostic, 'Vehicle service diagnostic', $attributes['diagnostic_id'] ?? null);
        $attributes['severity'] = $this->domain->normalizeEnum('diagnostic severity', $attributes['severity'] ?? null, config('vehicle-service.severities', []), config('vehicle-service.severities.0', 'info'));

        return $attributes;
    }

    private function prepareInspectionAttributes(array $attributes, int|string $tenantId): array
    {
        $this->requiredJobCard($tenantId, $attributes['job_card_id'] ?? null);
        $attributes['inspection_phase'] = $this->domain->normalizeEnum('inspection phase', $attributes['inspection_phase'] ?? null, config('vehicle-service.service_phases', []), null);
        $attributes['overall_result'] = $this->domain->normalizeEnum('inspection result', $attributes['overall_result'] ?? null, config('vehicle-service.overall_results', []), config('vehicle-service.overall_results.0', 'pass'));

        return $attributes;
    }

    private function prepareInspectionLineAttributes(array $attributes, int|string $tenantId): array
    {
        $inspection = $this->domain->assertTenantInspection($tenantId, $attributes['inspection_id'] ?? null);
        $this->assertRequiredRecord($inspection, 'Vehicle service inspection', $attributes['inspection_id'] ?? null);
        $attributes['result'] = $this->domain->normalizeEnum('inspection result', $attributes['result'] ?? null, config('vehicle-service.inspection_line_results', []), config('vehicle-service.inspection_line_results.3', 'not_tested'));

        return $attributes;
    }

    private function recalculateForResourceChange(string $resource, Model $record, int|string $tenantId): void
    {
        match ($resource) {
            'job_cards' => $this->recalculateJobCard($tenantId, $record->getKey()),
            'job_card_lines', 'labor_items', 'non_inventory_items' => $this->recalculateJobCard($tenantId, $record->job_card_id),
            default => null,
        };
    }

    private function reloadRecord(string $resource, int|string $tenantId, int|string $id): Model
    {
        return $this->find($resource, $tenantId, $id);
    }

    /**
     * @return array{resource: string, id: int|string}|null
     */
    private function parentReference(string $resource, Model $record): ?array
    {
        return match ($resource) {
            'job_card_lines', 'labor_items', 'non_inventory_items' => ['resource' => 'job_cards', 'id' => $record->job_card_id],
            default => null,
        };
    }

    /**
     * @param  array{resource: string, id: int|string}|null  $parent
     */
    private function recalculateParentReference(int|string $tenantId, ?array $parent): void
    {
        if ($parent !== null && $parent['resource'] === 'job_cards') {
            $this->recalculateJobCard($tenantId, $parent['id']);
        }
    }

    private function requiredJobCard(int|string $tenantId, int|string|null $id): Model
    {
        $jobCard = $this->domain->assertTenantJobCard($tenantId, $id);
        $this->assertRequiredRecord($jobCard, 'Vehicle service job card', $id);

        return $jobCard;
    }

    private function assertRequiredRecord(?Model $record, string $resource, int|string|null $id): void
    {
        if ($record === null) {
            throw VehicleServiceRecordNotFoundException::for($resource, $id);
        }
    }

    /**
     * @param  array{resource: string, id: int|string}|null  $left
     * @param  array{resource: string, id: int|string}|null  $right
     */
    private function sameParentReference(?array $left, ?array $right): bool
    {
        if ($left === null || $right === null) {
            return $left === $right;
        }

        return $left['resource'] === $right['resource'] && (string) $left['id'] === (string) $right['id'];
    }
}
