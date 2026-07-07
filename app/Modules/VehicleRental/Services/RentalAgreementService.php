<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use BackedEnum;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalReservation;
use RuntimeException;

final class RentalAgreementService
{
    private const STRUCTURAL_DRAFT_FIELDS = [
        'customer_id',
        'supplier_id',
        'starts_at',
        'ends_at',
        'rental_mode',
        'billing_cycle',
        'billing_basis',
        'proration_rule',
        'billing_timezone',
        'payment_term_days',
        'currency_id',
    ];

    private const TRANSITIONS = [
        'draft' => ['active', 'cancelled'],
        'active' => ['suspended', 'completed', 'terminated'],
        'suspended' => ['active', 'terminated', 'cancelled'],
        'completed' => [],
        'terminated' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly RentalNumberService $numbers,
        private readonly RentalRateVersionService $rates,
        private readonly RentalReferenceValidator $references,
        private readonly RentalStatusHistoryService $history,
    ) {}

    public function create(array $data, int $tenantId, ?int $organizationUnitId, ?int $userId): RentalAgreement
    {
        return DB::transaction(function () use ($data, $tenantId, $organizationUnitId, $userId): RentalAgreement {
            $kind = RentalAgreementKind::from((string) $data['agreement_kind']);
            $this->assertParty($kind, $data);
            $this->assertDepositAllowed($kind, $data);
            $this->validateReferences($kind, $data, $tenantId, $organizationUnitId);
            $reservation = null;
            if (! empty($data['reservation_id'])) {
                $reservation = RentalReservation::query()
                    ->forContext($tenantId, $organizationUnitId)
                    ->lockForUpdate()
                    ->findOrFail($data['reservation_id']);
                if ($reservation->status !== RentalReservationStatus::Confirmed) {
                    throw new InvalidArgumentException('Only a confirmed reservation can be converted.');
                }
                $this->assertReservationExpectedVersion(
                    $reservation,
                    (int) $data['expected_reservation_version'],
                );
                if ($kind !== RentalAgreementKind::CustomerRental
                    || (int) $reservation->customer_id !== (int) $data['customer_id']) {
                    throw new InvalidArgumentException('Reservation and agreement customer must match.');
                }
            }

            $agreement = RentalAgreement::query()->create([
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'agreement_number' => $data['agreement_number'] ?? $this->numbers->next(
                    $tenantId,
                    $organizationUnitId,
                    'vehicle_rental_agreement',
                    'RA-',
                ),
                'agreement_kind' => $kind->value,
                'reservation_id' => $reservation?->getKey(),
                'customer_id' => $data['customer_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'agreement_date' => $data['agreement_date'],
                'executed_at' => $data['executed_at'] ?? null,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'legal_context' => $data['legal_context'] ?? null,
                'rental_mode' => $data['rental_mode'],
                'billing_cycle' => $data['billing_cycle'],
                'billing_basis' => $data['billing_basis'],
                'proration_rule' => $data['proration_rule'] ?? 'exact_day_count',
                'billing_timezone' => $data['billing_timezone'] ?? $this->defaultBillingTimezone(),
                'payment_term_days' => $data['payment_term_days'] ?? null,
                'currency_id' => $data['currency_id'],
                'status' => RentalAgreementStatus::Draft->value,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->replaceTerms($agreement, $data['terms'] ?? [], $userId);

            if (! empty($data['rate_version'])) {
                $version = $this->rates->createDraft($agreement, $data['rate_version'], $userId);
                if (($data['activate_rate_version'] ?? true) === true) {
                    $this->rates->activate($version, (int) $version->row_version, $userId);
                }
            }

            if ($kind === RentalAgreementKind::CustomerRental && ! empty($data['deposit'])) {
                $deposit = $data['deposit'];
                $required = (string) ($deposit['required_amount'] ?? '0.000000');
                $agreement->depositRequirement()->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'agreement_kind' => RentalAgreementKind::CustomerRental->value,
                    'customer_id' => $agreement->customer_id,
                    'required_amount' => $required,
                    'currency_id' => $deposit['currency_id'] ?? $agreement->currency_id,
                    'due_date' => $deposit['due_date'] ?? null,
                    'is_refundable' => $deposit['is_refundable'] ?? true,
                    'balance_amount' => $required,
                    'status' => 'pending',
                    'remarks' => $deposit['remarks'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            if ($reservation !== null) {
                $previousReservationStatus = $reservation->status;
                $reservation->status = RentalReservationStatus::Converted;
                $reservation->row_version = (int) $reservation->row_version + 1;
                $reservation->updated_by = $userId;
                $reservation->save();
                $this->history->record($reservation, $previousReservationStatus->value, RentalReservationStatus::Converted->value, $userId);
            }
            $this->history->record($agreement, null, RentalAgreementStatus::Draft->value, $userId);

            return $agreement->load($this->relations());
        }, 3);
    }

    public function updateDraft(
        RentalAgreement $agreement,
        array $data,
        int $expectedVersion,
        ?int $userId,
    ): RentalAgreement {
        return DB::transaction(function () use ($agreement, $data, $expectedVersion, $userId): RentalAgreement {
            $agreement = RentalAgreement::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->lockForUpdate()
                ->findOrFail($agreement->getKey());
            $this->assertExpectedVersion($agreement, $expectedVersion);
            if ($agreement->status !== RentalAgreementStatus::Draft) {
                throw new InvalidArgumentException('Only draft agreements can be edited.');
            }

            $kind = $agreement->agreement_kind;
            $merged = array_merge($agreement->toArray(), $data);
            $this->assertParty($kind, $merged);
            $this->assertStructuralDraftChangesAllowed($agreement, $data);
            $this->validateReferences(
                $kind,
                $merged,
                (int) $agreement->tenant_id,
                $agreement->organization_unit_id,
            );

            $agreement->fill(array_intersect_key($data, array_flip([
                'customer_id',
                'supplier_id',
                'agreement_date',
                'executed_at',
                'starts_at',
                'ends_at',
                'legal_context',
                'rental_mode',
                'billing_cycle',
                'billing_basis',
                'proration_rule',
                'billing_timezone',
                'payment_term_days',
                'currency_id',
                'remarks',
            ])));
            $agreement->row_version = $expectedVersion + 1;
            $agreement->updated_by = $userId;
            $agreement->save();

            if (array_key_exists('terms', $data)) {
                $this->syncTerms($agreement, $data['terms'] ?? [], $userId);
            }

            return $agreement->refresh()->load($this->relations());
        }, 3);
    }

    public function transition(
        RentalAgreement $agreement,
        RentalAgreementStatus $to,
        int $expectedVersion,
        ?int $userId = null,
        ?string $reason = null,
    ): RentalAgreement {
        return DB::transaction(function () use ($agreement, $to, $expectedVersion, $userId, $reason): RentalAgreement {
            $agreement = RentalAgreement::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->lockForUpdate()
                ->findOrFail($agreement->getKey());
            $this->assertExpectedVersion($agreement, $expectedVersion);
            $from = $agreement->status;
            if ($from === $to) {
                return $agreement->load($this->relations());
            }
            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid agreement transition from {$from->value} to {$to->value}.");
            }
            if ($to === RentalAgreementStatus::Active
                && ! $agreement->rateVersions()->where('status', 'active')->exists()) {
                throw new InvalidArgumentException('An active rate version is required before agreement activation.');
            }
            if (in_array($to, [
                RentalAgreementStatus::Completed,
                RentalAgreementStatus::Terminated,
                RentalAgreementStatus::Cancelled,
            ], true) && $agreement->allocations()->whereIn('status', ['planned', 'active'])->exists()) {
                throw new InvalidArgumentException('Close or cancel all planned and active vehicle allocations before ending the agreement.');
            }
            if (in_array($to, [RentalAgreementStatus::Completed, RentalAgreementStatus::Terminated], true)) {
                $agreement->actual_ended_at ??= now();
            }

            $agreement->status = $to;
            $agreement->termination_reason = $to === RentalAgreementStatus::Terminated
                ? $reason
                : $agreement->termination_reason;
            $agreement->approved_by = $to === RentalAgreementStatus::Active
                ? $userId
                : $agreement->approved_by;
            $agreement->approved_at = $to === RentalAgreementStatus::Active
                ? now()
                : $agreement->approved_at;
            $agreement->terminated_by = $to === RentalAgreementStatus::Terminated
                ? $userId
                : $agreement->terminated_by;
            $agreement->terminated_at = $to === RentalAgreementStatus::Terminated
                ? now()
                : $agreement->terminated_at;
            $agreement->row_version = $expectedVersion + 1;
            $agreement->updated_by = $userId;
            $agreement->save();
            $this->history->record($agreement, $from->value, $to->value, $userId, $reason);

            return $agreement->refresh()->load($this->relations());
        }, 3);
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RentalAgreement::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with(['reservation', 'customer', 'supplier', 'currency', 'activeRateVersion', 'allocations.vehicle']);
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $query) use ($search): void {
                $query->where('agreement_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $relation) => $relation->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn (Builder $relation) => $relation->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('allocations.vehicle', fn (Builder $relation) => $relation->where('registration_number', 'like', "%{$search}%"));
            });
        }
        foreach (['agreement_kind', 'status', 'customer_id', 'supplier_id'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('starts_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('starts_at', '<=', $filters['date_to']);
        }

        return $query->latest('agreement_date')->latest('id')->paginate($perPage);
    }

    public function relations(): array
    {
        return [
            'reservation',
            'customer',
            'supplier',
            'currency',
            'terms',
            'rateVersions.components',
            'activeRateVersion.components',
            'allocations.vehicle.make',
            'allocations.vehicle.model',
            'allocations.sourceAllocation.agreement.supplier',
            'driverAssignments.employee',
            'depositRequirement.links.payment',
            'depositRequirement.links.invoice',
            'depositRequirement.links.reversesLink',
        ];
    }

    private function validateReferences(
        RentalAgreementKind $kind,
        array $data,
        int $tenantId,
        ?int $organizationUnitId,
    ): void {
        $this->references->currency((int) $data['currency_id']);

        if ($kind === RentalAgreementKind::CustomerRental) {
            $this->references->customer((int) $data['customer_id'], $tenantId, $organizationUnitId);
        } else {
            $this->references->supplier((int) $data['supplier_id'], $tenantId, $organizationUnitId);
        }

        if (! empty($data['deposit']['currency_id'])) {
            $this->references->currency((int) $data['deposit']['currency_id']);
        }
    }

    private function assertDepositAllowed(RentalAgreementKind $kind, array $data): void
    {
        if ($kind === RentalAgreementKind::CustomerRental
            || ! array_key_exists('deposit', $data)
            || $data['deposit'] === null) {
            return;
        }

        throw ValidationException::withMessages([
            'deposit' => ['Security deposits are supported only for customer rental agreements.'],
        ]);
    }

    private function assertParty(RentalAgreementKind $kind, array $data): void
    {
        if ($kind === RentalAgreementKind::CustomerRental
            && (empty($data['customer_id']) || ! empty($data['supplier_id']))) {
            throw new InvalidArgumentException('Customer rental agreement requires only a customer.');
        }
        if ($kind === RentalAgreementKind::OwnerSupply
            && (empty($data['supplier_id']) || ! empty($data['customer_id']))) {
            throw new InvalidArgumentException('Owner supply agreement requires only a supplier/vehicle owner.');
        }
    }

    private function assertStructuralDraftChangesAllowed(RentalAgreement $agreement, array $data): void
    {
        $changedFields = array_values(array_filter(
            self::STRUCTURAL_DRAFT_FIELDS,
            fn (string $field): bool => array_key_exists($field, $data)
                && ! $this->fieldValueMatches($agreement->{$field}, $data[$field]),
        ));

        if ($changedFields === []) {
            return;
        }

        $hasDependentRecords = $agreement->allocations()->exists()
            || $agreement->rateVersions()->exists()
            || $agreement->depositRequirement()->exists();
        if (! $hasDependentRecords) {
            return;
        }

        throw ValidationException::withMessages([
            'agreement' => [
                'Agreement party, period, billing, payment term, and currency fields cannot be changed after allocations, rate versions, or deposit requirements exist.',
            ],
        ]);
    }

    private function fieldValueMatches(mixed $current, mixed $next): bool
    {
        if ($current instanceof BackedEnum) {
            $current = $current->value;
        }

        if ($current instanceof DateTimeInterface) {
            if ($next === null || $next === '') {
                return false;
            }

            return CarbonImmutable::parse($next)->equalTo(CarbonImmutable::instance($current));
        }

        if ($current === null || $next === null) {
            return $current === $next;
        }

        return (string) $current === (string) $next;
    }

    private function assertExpectedVersion(RentalAgreement $agreement, int $expectedVersion): void
    {
        if ((int) $agreement->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_version' => ['The rental agreement changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function assertReservationExpectedVersion(RentalReservation $reservation, int $expectedVersion): void
    {
        if ((int) $reservation->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_reservation_version' => ['The rental reservation changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    /** @param list<array<string, mixed>> $terms */
    private function replaceTerms(RentalAgreement $agreement, array $terms, ?int $userId): void
    {
        foreach (array_values($terms) as $index => $term) {
            $agreement->terms()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'sequence' => $term['sequence'] ?? ($index + 1),
                'term_code' => $term['term_code'] ?? null,
                'title' => $term['title'] ?? null,
                'content' => $term['content'],
                'is_printable' => $term['is_printable'] ?? true,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    /** @param list<array<string, mixed>> $terms */
    private function syncTerms(RentalAgreement $agreement, array $terms, ?int $userId): void
    {
        $existing = $agreement->terms()->lockForUpdate()->get();
        $byId = $existing->keyBy(fn ($term): int => (int) $term->getKey());
        $bySequence = $existing->keyBy(fn ($term): int => (int) $term->sequence);
        $seen = [];

        foreach (array_values($terms) as $index => $term) {
            $sequence = (int) ($term['sequence'] ?? ($index + 1));
            $target = ! empty($term['id'])
                ? $byId->get((int) $term['id'])
                : $bySequence->get($sequence);

            if (! empty($term['id']) && $target === null) {
                throw ValidationException::withMessages([
                    'terms' => ['One or more agreement terms no longer exist. Reload and review the latest version.'],
                ]);
            }

            $sequenceOwner = $bySequence->get($sequence);
            if ($target !== null && $sequenceOwner !== null && (int) $sequenceOwner->getKey() !== (int) $target->getKey()) {
                throw ValidationException::withMessages([
                    'terms' => ['Two agreement terms cannot use the same sequence.'],
                ]);
            }

            if ($target === null) {
                $target = $agreement->terms()->create([
                    'tenant_id' => $agreement->tenant_id,
                    'organization_unit_id' => $agreement->organization_unit_id,
                    'sequence' => $sequence,
                    'term_code' => $term['term_code'] ?? null,
                    'title' => $term['title'] ?? null,
                    'content' => $term['content'],
                    'is_printable' => $term['is_printable'] ?? true,
                    'is_active' => true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            } else {
                $target->forceFill([
                    'sequence' => $sequence,
                    'term_code' => $term['term_code'] ?? null,
                    'title' => $term['title'] ?? null,
                    'content' => $term['content'],
                    'is_printable' => $term['is_printable'] ?? true,
                    'is_active' => true,
                    'row_version' => (int) $target->row_version + 1,
                    'updated_by' => $userId,
                ])->save();
            }

            $seen[(int) $target->getKey()] = true;
        }

        foreach ($existing as $term) {
            if (! ($seen[(int) $term->getKey()] ?? false) && (bool) $term->is_active) {
                $term->forceFill([
                    'is_active' => false,
                    'row_version' => (int) $term->row_version + 1,
                    'updated_by' => $userId,
                ])->save();
            }
        }
    }

    private function defaultBillingTimezone(): string
    {
        $timezone = trim((string) config(
            'vehicle_rental.billing_timezone',
            config('app.timezone', 'UTC'),
        ));
        if ($timezone === '' || ! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new RuntimeException('Vehicle Rental billing timezone configuration is invalid.');
        }

        return $timezone;
    }
}
