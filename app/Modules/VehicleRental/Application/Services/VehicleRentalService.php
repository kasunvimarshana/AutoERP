<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Services;

use App\Support\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\VehicleRental\Application\Actions\DeleteVehicleRentalRecordAction;
use Modules\VehicleRental\Application\Actions\FindVehicleRentalRecordAction;
use Modules\VehicleRental\Application\Actions\ListVehicleRentalRecordsAction;
use Modules\VehicleRental\Application\Actions\PersistVehicleRentalRecordAction;
use Modules\VehicleRental\Application\DTOs\VehicleRentalRecordData;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeRunningChartRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorRunningChartRepositoryInterface;
use Modules\VehicleRental\Domain\Exceptions\VehicleRentalIntegrityException;
use Modules\VehicleRental\Domain\Exceptions\VehicleRentalRecordNotFoundException;
use Modules\VehicleRental\Domain\Services\VehicleRentalDomainService;

class VehicleRentalService
{
    public function __construct(
        private readonly Container $container,
        private readonly TenantRepositoryInterface $tenants,
        private readonly VehicleRentalLessorRunningChartRepositoryInterface $lessorRunningCharts,
        private readonly VehicleRentalLesseeRunningChartRepositoryInterface $lesseeRunningCharts,
        private readonly VehicleRentalDomainService $domain,
        private readonly ListVehicleRentalRecordsAction $listRecords,
        private readonly FindVehicleRentalRecordAction $findRecord,
        private readonly PersistVehicleRentalRecordAction $persistRecord,
        private readonly DeleteVehicleRentalRecordAction $deleteRecord,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function definition(string $resource): array
    {
        $key = $this->domain->normalizeResourceKey($resource);
        $definition = config("vehicle-rental.resources.{$key}");

        if (! is_array($definition)) {
            throw VehicleRentalRecordNotFoundException::for('Vehicle rental resource', $resource);
        }

        return ['key' => $key, ...$definition];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(string $resource, int|string $tenantId, array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $this->ensureTenantExists($tenantId);

        return $this->listRecords->execute($this->repository($resource), ['tenant_id' => $tenantId, ...$filters], $perPage);
    }

    public function find(string $resource, int|string $tenantId, int|string $id): Model
    {
        $definition = $this->definition($resource);

        return $this->findRecord->execute($this->repository($resource), $definition['label'] ?? $resource, $tenantId, $id);
    }

    public function create(string $resource, VehicleRentalRecordData $data): Model
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

    public function update(string $resource, int|string $tenantId, int|string $id, VehicleRentalRecordData $data): Model
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

            if (! $this->sameParentReference($originalParent, $this->parentReference($definition['key'], $updated))) {
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

    public function recalculateLessorRunningChart(int|string $tenantId, int|string $id): Model
    {
        $runningChart = $this->find('lessor_running_charts', $tenantId, $id);

        return $this->lessorRunningCharts->transaction(fn (): Model => $this->lessorRunningCharts->update($runningChart, [
            ...$this->domain->calculateLessorRunningChartTotals($runningChart),
            'km_travelled' => $this->domain->normalizeDecimal(max(0.0, (float) $runningChart->end_km - (float) $runningChart->start_km)),
            'row_version' => $this->domain->nextRowVersion($runningChart),
        ]));
    }

    public function recalculateLesseeRunningChart(int|string $tenantId, int|string $id): Model
    {
        $runningChart = $this->find('lessee_running_charts', $tenantId, $id);

        return $this->lesseeRunningCharts->transaction(fn (): Model => $this->lesseeRunningCharts->update($runningChart, [
            ...$this->domain->calculateLesseeRunningChartTotals($runningChart),
            'km_travelled' => $this->domain->normalizeDecimal(max(0.0, (float) $runningChart->end_km - (float) $runningChart->start_km)),
            'row_version' => $this->domain->nextRowVersion($runningChart),
        ]));
    }

    private function ensureTenantExists(int|string $tenantId): void
    {
        if ($this->tenants->findById($tenantId) === null) {
            throw VehicleRentalRecordNotFoundException::for('Tenant', $tenantId);
        }
    }

    private function repository(string $resource): BaseRepositoryInterface
    {
        $definition = $this->definition($resource);
        $repository = $this->container->make($definition['repository']);

        if (! $repository instanceof BaseRepositoryInterface) {
            throw VehicleRentalIntegrityException::rule("Repository for [{$definition['key']}] must implement BaseRepositoryInterface.");
        }

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareAttributes(string $resource, array $attributes, int|string $tenantId): array
    {
        foreach (config('vehicle-rental.calculated_columns', []) as $calculatedColumn) {
            unset($attributes[$calculatedColumn]);
        }

        $attributes = [...$this->normalizeScalars($attributes), 'tenant_id' => $tenantId];
        $attributes['metadata'] = $this->domain->normalizeMetadata($attributes['metadata'] ?? null);

        return match ($resource) {
            'lessor_agreements', 'lessee_agreements' => $this->prepareAgreementAttributes($attributes),
            'lessor_running_charts' => $this->prepareLessorRunningChartAttributes($attributes, $tenantId),
            'lessee_running_charts' => $this->prepareLesseeRunningChartAttributes($attributes, $tenantId),
            'lessor_credit_notes', 'lessor_debit_notes' => $this->prepareLessorNoteAttributes($attributes, $tenantId),
            'lessee_credit_notes', 'lessee_debit_notes' => $this->prepareLesseeNoteAttributes($attributes, $tenantId),
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

        foreach (config('vehicle-rental.decimal_columns', []) as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== null) {
                $attributes[$column] = $this->domain->normalizeDecimal($attributes[$column]);
            }
        }

        return $attributes;
    }

    private function prepareAgreementAttributes(array $attributes): array
    {
        $attributes['agreement_type'] = $this->domain->normalizeEnum('agreement type', $attributes['agreement_type'] ?? null, config('vehicle-rental.agreement_types', []), 'custom');
        $attributes['status'] = $this->domain->normalizeEnum('agreement status', $attributes['status'] ?? null, config('vehicle-rental.agreement_statuses', []), 'draft');

        return $attributes;
    }

    private function prepareLessorRunningChartAttributes(array $attributes, int|string $tenantId): array
    {
        $this->requiredLessorAgreement($tenantId, $attributes['lessor_agreement_id'] ?? null);
        $this->domain->assertTenantLesseeAgreement($tenantId, $attributes['lessee_agreement_id'] ?? null);

        return $this->domain->prepareRunningChartAttributes($attributes);
    }

    private function prepareLesseeRunningChartAttributes(array $attributes, int|string $tenantId): array
    {
        $this->requiredLesseeAgreement($tenantId, $attributes['lessee_agreement_id'] ?? null);
        $this->domain->assertTenantLessorAgreement($tenantId, $attributes['lessor_agreement_id'] ?? null);

        return $this->domain->prepareRunningChartAttributes($attributes);
    }

    private function prepareLessorNoteAttributes(array $attributes, int|string $tenantId): array
    {
        $this->requiredLessorAgreement($tenantId, $attributes['lessor_agreement_id'] ?? null);

        return $attributes;
    }

    private function prepareLesseeNoteAttributes(array $attributes, int|string $tenantId): array
    {
        $this->requiredLesseeAgreement($tenantId, $attributes['lessee_agreement_id'] ?? null);

        return $attributes;
    }

    private function recalculateForResourceChange(string $resource, Model $record, int|string $tenantId): void
    {
        match ($resource) {
            'lessor_running_charts' => $this->recalculateLessorRunningChart($tenantId, $record->getKey()),
            'lessee_running_charts' => $this->recalculateLesseeRunningChart($tenantId, $record->getKey()),
            'lessor_credit_notes', 'lessor_debit_notes' => $this->recalculateMatchingLessorRunningCharts($tenantId, $record),
            'lessee_credit_notes', 'lessee_debit_notes' => $this->recalculateMatchingLesseeRunningCharts($tenantId, $record),
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
            'lessor_credit_notes', 'lessor_debit_notes' => ['resource' => 'lessor_note_date', 'id' => $record->lessor_agreement_id.'|'.$record->entry_date],
            'lessee_credit_notes', 'lessee_debit_notes' => ['resource' => 'lessee_note_date', 'id' => $record->lessee_agreement_id.'|'.$record->entry_date],
            default => null,
        };
    }

    /**
     * @param  array{resource: string, id: int|string}|null  $parent
     */
    private function recalculateParentReference(int|string $tenantId, ?array $parent): void
    {
        if ($parent === null) {
            return;
        }

        [$agreementId, $entryDate] = explode('|', (string) $parent['id'], 2);

        if ($parent['resource'] === 'lessor_note_date') {
            $this->lessorRunningCharts->getWhere(['tenant_id' => $tenantId, 'lessor_agreement_id' => $agreementId, 'log_date' => $entryDate])
                ->each(fn (Model $chart) => $this->recalculateLessorRunningChart($tenantId, $chart->getKey()));
        }

        if ($parent['resource'] === 'lessee_note_date') {
            $this->lesseeRunningCharts->getWhere(['tenant_id' => $tenantId, 'lessee_agreement_id' => $agreementId, 'log_date' => $entryDate])
                ->each(fn (Model $chart) => $this->recalculateLesseeRunningChart($tenantId, $chart->getKey()));
        }
    }

    private function recalculateMatchingLessorRunningCharts(int|string $tenantId, Model $note): void
    {
        $this->lessorRunningCharts->getWhere(['tenant_id' => $tenantId, 'lessor_agreement_id' => $note->lessor_agreement_id, 'log_date' => $note->entry_date])
            ->each(fn (Model $chart) => $this->recalculateLessorRunningChart($tenantId, $chart->getKey()));
    }

    private function recalculateMatchingLesseeRunningCharts(int|string $tenantId, Model $note): void
    {
        $this->lesseeRunningCharts->getWhere(['tenant_id' => $tenantId, 'lessee_agreement_id' => $note->lessee_agreement_id, 'log_date' => $note->entry_date])
            ->each(fn (Model $chart) => $this->recalculateLesseeRunningChart($tenantId, $chart->getKey()));
    }

    private function requiredLessorAgreement(int|string $tenantId, int|string|null $id): Model
    {
        $agreement = $this->domain->assertTenantLessorAgreement($tenantId, $id);

        if ($agreement === null) {
            throw VehicleRentalRecordNotFoundException::for('Vehicle rental lessor agreement', $id);
        }

        return $agreement;
    }

    private function requiredLesseeAgreement(int|string $tenantId, int|string|null $id): Model
    {
        $agreement = $this->domain->assertTenantLesseeAgreement($tenantId, $id);

        if ($agreement === null) {
            throw VehicleRentalRecordNotFoundException::for('Vehicle rental lessee agreement', $id);
        }

        return $agreement;
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
