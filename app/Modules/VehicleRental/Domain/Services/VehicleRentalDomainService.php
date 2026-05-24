<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Domain\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeRunningChartRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementRepositoryInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorRunningChartRepositoryInterface;
use Modules\VehicleRental\Domain\Exceptions\VehicleRentalIntegrityException;
use Modules\VehicleRental\Domain\Exceptions\VehicleRentalRecordNotFoundException;

class VehicleRentalDomainService
{
    public function __construct(
        private readonly VehicleRentalLessorAgreementRepositoryInterface $lessorAgreements,
        private readonly VehicleRentalLesseeAgreementRepositoryInterface $lesseeAgreements,
        private readonly VehicleRentalLessorRunningChartRepositoryInterface $lessorRunningCharts,
        private readonly VehicleRentalLesseeRunningChartRepositoryInterface $lesseeRunningCharts,
        private readonly VehicleRentalLessorAgreementCreditNoteRepositoryInterface $lessorCreditNotes,
        private readonly VehicleRentalLessorAgreementDebitNoteRepositoryInterface $lessorDebitNotes,
        private readonly VehicleRentalLesseeAgreementCreditNoteRepositoryInterface $lesseeCreditNotes,
        private readonly VehicleRentalLesseeAgreementDebitNoteRepositoryInterface $lesseeDebitNotes,
    ) {}

    public function normalizeResourceKey(string $resource): string
    {
        return match (str_replace('-', '_', strtolower(trim($resource)))) {
            'lessor_agreements', 'inward_agreements', 'supplier_agreements' => 'lessor_agreements',
            'lessee_agreements', 'outward_agreements', 'customer_agreements' => 'lessee_agreements',
            'lessor_running_charts', 'inward_running_charts', 'supplier_running_charts' => 'lessor_running_charts',
            'lessee_running_charts', 'outward_running_charts', 'customer_running_charts', 'running_charts' => 'lessee_running_charts',
            'lessor_credit_notes', 'inward_credit_notes', 'supplier_credit_notes' => 'lessor_credit_notes',
            'lessor_debit_notes', 'inward_debit_notes', 'supplier_debit_notes' => 'lessor_debit_notes',
            'lessee_credit_notes', 'outward_credit_notes', 'customer_credit_notes' => 'lessee_credit_notes',
            'lessee_debit_notes', 'outward_debit_notes', 'customer_debit_notes' => 'lessee_debit_notes',
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
        return number_format((float) ($value ?? 0), (int) config('vehicle-rental.precision.scale', 4), '.', '');
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
            throw VehicleRentalIntegrityException::rule("Unsupported {$family} value [{$value}].");
        }

        return $normalized;
    }

    public function assertRowVersion(?int $expected, Model $record): void
    {
        if ($expected !== null && (int) $record->row_version !== $expected) {
            throw VehicleRentalIntegrityException::conflict("Record version conflict. Expected [{$expected}], current [{$record->row_version}].");
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
        $immutable = config("vehicle-rental.immutable.{$resource}", []);

        if (($immutable['after_create'] ?? false) && $updating) {
            throw VehicleRentalIntegrityException::rule("{$definition['label']} records cannot be modified after creation.");
        }

        $statusColumn = $immutable['status_column'] ?? null;

        if ($statusColumn !== null && in_array((string) $record->{$statusColumn}, $immutable['statuses'] ?? [], true)) {
            throw VehicleRentalIntegrityException::rule("{$definition['label']} is locked in status [{$record->{$statusColumn}}].");
        }
    }

    public function assertTenantLessorAgreement(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->lessorAgreements, 'Vehicle rental lessor agreement', $tenantId, $id);
    }

    public function assertTenantLesseeAgreement(int|string $tenantId, int|string|null $id): ?Model
    {
        return $this->assertTenantRecord($this->lesseeAgreements, 'Vehicle rental lessee agreement', $tenantId, $id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function prepareRunningChartAttributes(array $attributes): array
    {
        $attributes['km_travelled'] = $this->normalizeDecimal(
            max(0.0, (float) ($attributes['end_km'] ?? 0) - (float) ($attributes['start_km'] ?? 0)),
        );
        $attributes['debit_note_total'] = $this->normalizeDecimal(0);
        $attributes['credit_note_total'] = $this->normalizeDecimal(0);

        return $attributes;
    }

    /**
     * @return array{debit_note_total: string, credit_note_total: string}
     */
    public function calculateLessorRunningChartTotals(Model $runningChart): array
    {
        return [
            'debit_note_total' => $this->sumNotesByDate($this->lessorDebitNotes->getWhere([
                'tenant_id' => $runningChart->tenant_id,
                'lessor_agreement_id' => $runningChart->lessor_agreement_id,
                'entry_date' => $runningChart->log_date,
            ])),
            'credit_note_total' => $this->sumNotesByDate($this->lessorCreditNotes->getWhere([
                'tenant_id' => $runningChart->tenant_id,
                'lessor_agreement_id' => $runningChart->lessor_agreement_id,
                'entry_date' => $runningChart->log_date,
            ])),
        ];
    }

    /**
     * @return array{debit_note_total: string, credit_note_total: string}
     */
    public function calculateLesseeRunningChartTotals(Model $runningChart): array
    {
        return [
            'debit_note_total' => $this->sumNotesByDate($this->lesseeDebitNotes->getWhere([
                'tenant_id' => $runningChart->tenant_id,
                'lessee_agreement_id' => $runningChart->lessee_agreement_id,
                'entry_date' => $runningChart->log_date,
            ])),
            'credit_note_total' => $this->sumNotesByDate($this->lesseeCreditNotes->getWhere([
                'tenant_id' => $runningChart->tenant_id,
                'lessee_agreement_id' => $runningChart->lessee_agreement_id,
                'entry_date' => $runningChart->log_date,
            ])),
        ];
    }

    private function assertTenantRecord(mixed $repository, string $resource, int|string $tenantId, int|string|null $id): ?Model
    {
        if ($id === null) {
            return null;
        }

        $record = $repository->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw VehicleRentalRecordNotFoundException::for($resource, $id);
        }

        return $record;
    }

    private function sumNotesByDate(Collection $notes): string
    {
        return $this->normalizeDecimal($notes->sum(fn (Model $note): float => (float) $note->amount));
    }
}
