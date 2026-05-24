<?php

declare(strict_types=1);

namespace Modules\VehicleService\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
use Modules\VehicleService\Domain\Aggregates\VehicleServiceJobCardAggregate;
use Modules\VehicleService\Domain\Exceptions\VehicleServiceIntegrityException;
use Modules\VehicleService\Domain\Exceptions\VehicleServiceRecordNotFoundException;

class VehicleServiceDomainService
{
    public function __construct(
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
    ) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (str_replace('-', '_', strtolower(trim($resource)))) {
            'service_types', 'types' => 'service_types',
            'job_cards', 'jobs', 'work_orders', 'workorders' => 'job_cards',
            'job_card_lines', 'lines', 'parts', 'parts_lines' => 'job_card_lines',
            'labor_items', 'labour_items', 'labor_lines', 'labour_lines' => 'labor_items',
            'non_inventory_items', 'sundries', 'external_items', 'misc_items' => 'non_inventory_items',
            'labor_assignments', 'labour_assignments', 'technician_assignments' => 'labor_assignments',
            'diagnostics' => 'diagnostics',
            'diagnostic_lines' => 'diagnostic_lines',
            'inspections' => 'inspections',
            'inspection_lines' => 'inspection_lines',
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
        return number_format((float) ($value ?? 0), (int) config('vehicle-service.precision.scale', 4), '.', '');
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
            throw VehicleServiceIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected === null) {
            return;
        }

        if ((int) $record->row_version !== $expected) {
            throw VehicleServiceIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
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
        $immutable = config("vehicle-service.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw VehicleServiceIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw VehicleServiceIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantServiceType(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->serviceTypes, 'Vehicle service type', $tenantId, $id);
    }

    public function assertTenantJobCard(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->jobCards, 'Vehicle service job card', $tenantId, $id);
    }

    public function assertTenantLaborItem(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->laborItems, 'Vehicle service labor item', $tenantId, $id);
    }

    public function assertTenantDiagnostic(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->diagnostics, 'Vehicle service diagnostic', $tenantId, $id);
    }

    public function assertTenantInspection(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->inspections, 'Vehicle service inspection', $tenantId, $id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareChargeLineAmounts(array $attributes, bool $withIncentive = false): array
    {
        $attributes['quantity'] = $this->normalizeDecimal($attributes['quantity'] ?? 0);
        $attributes['unit_price'] = $this->normalizeDecimal($attributes['unit_price'] ?? 0);
        $attributes['tax_amount'] = $this->normalizeDecimal($attributes['tax_amount'] ?? 0);
        $attributes['discount_type'] = $this->normalizeEnum(
            'discount_type',
            $attributes['discount_type'] ?? null,
            config('vehicle-service.discount_types', []),
            config('vehicle-service.discount_types.0', 'fixed'),
        );
        $attributes['discount_value'] = $this->normalizeDecimal($attributes['discount_value'] ?? ($attributes['discount_amount'] ?? 0));

        $grossAmount = (float) $attributes['quantity'] * (float) $attributes['unit_price'];
        $discountAmount = $this->boundedAmount($attributes['discount_type'], $attributes['discount_value'], $grossAmount);

        $attributes['gross_amount'] = $this->normalizeDecimal($grossAmount);
        $attributes['discount_amount'] = $this->normalizeDecimal($discountAmount);
        $attributes['line_total'] = $this->normalizeDecimal($grossAmount - $discountAmount);
        $attributes['line_total_with_tax'] = $this->normalizeDecimal((float) $attributes['line_total'] + (float) $attributes['tax_amount']);

        if ($withIncentive) {
            $attributes['incentive_type'] = $this->normalizeEnum(
                'incentive_type',
                $attributes['incentive_type'] ?? null,
                config('vehicle-service.incentive_types', []),
                config('vehicle-service.incentive_types.0', 'fixed'),
            );
            $attributes['incentive_value'] = $this->normalizeDecimal($attributes['incentive_value'] ?? ($attributes['incentive_amount'] ?? 0));
            $attributes['incentive_amount'] = $this->normalizeDecimal(
                $this->boundedAmount($attributes['incentive_type'], $attributes['incentive_value'], (float) $attributes['line_total']),
            );
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareLaborAssignmentAmounts(array $attributes): array
    {
        $attributes['hours_worked'] = $this->normalizeDecimal($attributes['hours_worked'] ?? 0);
        $attributes['hourly_rate'] = $this->normalizeDecimal($attributes['hourly_rate'] ?? 0);
        $attributes['incentive_type'] = $this->normalizeEnum(
            'incentive_type',
            $attributes['incentive_type'] ?? null,
            config('vehicle-service.incentive_types', []),
            config('vehicle-service.incentive_types.0', 'fixed'),
        );
        $attributes['incentive_value'] = $this->normalizeDecimal($attributes['incentive_value'] ?? ($attributes['incentive_amount'] ?? 0));
        $attributes['incentive_amount'] = $this->normalizeDecimal(
            $this->boundedAmount($attributes['incentive_type'], $attributes['incentive_value'], (float) $attributes['hours_worked'] * (float) $attributes['hourly_rate']),
        );
        $attributes['role'] = $this->normalizeEnum('labor assignment role', $attributes['role'] ?? null, config('vehicle-service.assignment_roles', []), null);

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function calculateJobCardTotals(Collection $partsLines, Collection $nonInventoryItems, Collection $laborItems, array $attributes): array
    {
        return (new VehicleServiceJobCardAggregate(
            $partsLines,
            $nonInventoryItems,
            $laborItems,
            $attributes,
            (int) config('vehicle-service.precision.scale', 4),
            (string) config('vehicle-service.calculation.header_discount_base', 'net_after_line_discount'),
        ))->totals();
    }

    public function assertSameJobCard(Model $child, int|string $jobCardId, string $message): void
    {
        if ((string) $child->job_card_id !== (string) $jobCardId) {
            throw VehicleServiceIntegrityException::rule($message);
        }
    }

    private function boundedAmount(string $type, mixed $value, float $base): float
    {
        $amount = $type === 'percentage'
            ? $base * ((float) ($value ?? 0) / 100)
            : (float) ($value ?? 0);

        return min(max($amount, 0.0), max($base, 0.0));
    }

    private function assertTenantRecord(mixed $repository, string $resource, int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $repository->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw VehicleServiceRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }
}
