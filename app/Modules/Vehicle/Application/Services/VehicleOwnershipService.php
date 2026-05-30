<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\Services;

use DateTimeImmutable;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\Services\VehicleOwnershipServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleOwnershipRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;

final class VehicleOwnershipService implements VehicleOwnershipServiceInterface
{
    private const OWNERSHIP_TYPES = [
        'own',
        'customer',
        'supplier',
        'provider',
        'leased',
        'financed',
        'partner',
        'internal',
        'external',
        'other',
    ];

    private const OWNER_TYPES = [
        'company',
        'customer',
        'supplier',
        'provider',
        'employee',
        'partner',
        'external_party',
        'party',
        'other',
    ];

    private const OWNERSHIP_ROLES = [
        'legal_owner',
        'registered_owner',
        'operational_owner',
        'provider',
        'current_holder',
    ];

    private const SYSTEM_OWNER_TYPES = ['customer', 'supplier', 'provider', 'employee'];

    public function __construct(private readonly VehicleOwnershipRepositoryInterface $ownerships)
    {
    }

    public function list(int $tenantId, int $vehicleId): Result
    {
        $guard = $this->guardVehicleScope($tenantId, $vehicleId);
        if ($guard !== null) {
            return $guard;
        }

        return Result::success($this->ownerships->listForVehicle($tenantId, $vehicleId));
    }

    public function current(int $tenantId, int $vehicleId, string $ownershipRole = 'legal_owner'): Result
    {
        $guard = $this->guardVehicleScope($tenantId, $vehicleId);
        if ($guard !== null) {
            return $guard;
        }

        if (! in_array($ownershipRole, self::OWNERSHIP_ROLES, true)) {
            return $this->invalid('Invalid ownership role.');
        }

        return Result::success($this->ownerships->currentForVehicleRole($tenantId, $vehicleId, $ownershipRole));
    }

    public function create(int $vehicleId, array $payload): Result
    {
        $prepared = $this->preparePayload($vehicleId, $payload, true);
        if ($prepared instanceof Result) {
            return $prepared;
        }

        $tenantId = (int) $prepared['tenant_id'];
        $guard = $this->guardVehicleScope($tenantId, $vehicleId);
        if ($guard !== null) {
            return $guard;
        }

        return Result::success($this->ownerships->transaction(function () use ($prepared): DataRecord {
            if ((bool) $prepared['is_current']) {
                $this->ownerships->clearCurrentRole(
                    (int) $prepared['tenant_id'],
                    (int) $prepared['vehicle_id'],
                    (string) $prepared['ownership_role'],
                );
            }

            return $this->ownerships->create($prepared);
        }));
    }

    public function update(int $vehicleId, int $ownershipId, array $payload): Result
    {
        $prepared = $this->preparePayload($vehicleId, $payload, false);
        if ($prepared instanceof Result) {
            return $prepared;
        }

        $tenantId = (int) $prepared['tenant_id'];
        $guard = $this->guardVehicleScope($tenantId, $vehicleId);
        if ($guard !== null) {
            return $guard;
        }

        $existing = $this->ownerships->findForVehicle($tenantId, $vehicleId, $ownershipId);
        if ($existing === null) {
            return $this->notFound('Ownership record not found.');
        }

        if (isset($prepared['end_date']) && ! isset($prepared['start_date'])) {
            $existingStartDate = $existing->get('start_date');
            if (is_string($existingStartDate) && $this->dateIsBefore((string) $prepared['end_date'], $existingStartDate)) {
                return $this->invalid('End date must be on or after start date.');
            }
        }

        return Result::success($this->ownerships->transaction(function () use ($prepared, $ownershipId): DataRecord {
            if ((bool) ($prepared['is_current'] ?? false)) {
                $this->ownerships->clearCurrentRole(
                    (int) $prepared['tenant_id'],
                    (int) $prepared['vehicle_id'],
                    (string) $prepared['ownership_role'],
                    $ownershipId,
                );
            }

            return $this->ownerships->update($ownershipId, $prepared);
        }));
    }

    public function end(int $tenantId, int $vehicleId, int $ownershipId, string $endDate): Result
    {
        $guard = $this->guardVehicleScope($tenantId, $vehicleId);
        if ($guard !== null) {
            return $guard;
        }

        $record = $this->ownerships->findForVehicle($tenantId, $vehicleId, $ownershipId);
        if ($record === null) {
            return $this->notFound('Ownership record not found.');
        }

        if (! $this->isValidDate($endDate)) {
            return $this->invalid('End date must be a valid date.');
        }

        $startDate = $record->get('start_date');
        if (is_string($startDate) && $this->dateIsBefore($endDate, $startDate)) {
            return $this->invalid('End date must be on or after start date.');
        }

        return Result::success($this->ownerships->transaction(fn (): DataRecord => $this->ownerships->update($ownershipId, [
            'end_date' => $endDate,
            'is_current' => false,
        ])));
    }

    public function setCurrent(int $tenantId, int $vehicleId, int $ownershipId): Result
    {
        $guard = $this->guardVehicleScope($tenantId, $vehicleId);
        if ($guard !== null) {
            return $guard;
        }

        $record = $this->ownerships->findForVehicle($tenantId, $vehicleId, $ownershipId);
        if ($record === null) {
            return $this->notFound('Ownership record not found.');
        }

        return Result::success($this->ownerships->transaction(function () use ($record, $ownershipId): DataRecord {
            $this->ownerships->clearCurrentRole(
                (int) $record->require('tenant_id'),
                (int) $record->require('vehicle_id'),
                (string) $record->require('ownership_role'),
                $ownershipId,
            );

            return $this->ownerships->update($ownershipId, [
                'is_current' => true,
                'end_date' => null,
            ]);
        }));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|Result
     */
    private function preparePayload(int $vehicleId, array $payload, bool $creating): array|Result
    {
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        if ($tenantId < 1 || $vehicleId < 1) {
            return $this->invalid('Tenant and vehicle are required.');
        }

        $ownershipType = trim((string) ($payload['ownership_type'] ?? ''));
        $ownerType = trim((string) ($payload['owner_type'] ?? ''));
        $ownershipRole = trim((string) ($payload['ownership_role'] ?? ''));

        if (! in_array($ownershipType, self::OWNERSHIP_TYPES, true)) {
            return $this->invalid('Invalid ownership type.');
        }

        if (! in_array($ownerType, self::OWNER_TYPES, true)) {
            return $this->invalid('Invalid owner type.');
        }

        if (! in_array($ownershipRole, self::OWNERSHIP_ROLES, true)) {
            return $this->invalid('Invalid ownership role.');
        }

        if ($creating && empty($payload['start_date'])) {
            return $this->invalid('Start date is required.');
        }

        if (isset($payload['start_date']) && ! $this->isValidDate((string) $payload['start_date'])) {
            return $this->invalid('Start date must be a valid date.');
        }

        if (isset($payload['end_date']) && $payload['end_date'] !== null) {
            if (! $this->isValidDate((string) $payload['end_date'])) {
                return $this->invalid('End date must be a valid date.');
            }

            if (isset($payload['start_date']) && $this->dateIsBefore((string) $payload['end_date'], (string) $payload['start_date'])) {
                return $this->invalid('End date must be on or after start date.');
            }
        }

        if ($ownerType === 'external_party' && trim((string) ($payload['owner_name'] ?? '')) === '') {
            return $this->invalid('Owner name is required for external-party ownership.');
        }

        if (in_array($ownerType, self::SYSTEM_OWNER_TYPES, true)) {
            $ownerId = (int) ($payload['owner_id'] ?? 0);
            if ($ownerId < 1) {
                return $this->invalid('Owner id is required for system owner types.');
            }

            if (! $this->ownerships->ownerReferenceExists($ownerType, $ownerId, $tenantId)) {
                return $this->invalid('Owner id must reference a same-tenant owner record.');
            }
        }

        if ($ownerType === 'party' && (int) ($payload['party_id'] ?? 0) < 1) {
            return $this->invalid('Party id is required when owner type is party.');
        }

        if ($ownerType === 'party' && ! $this->ownerships->ownerReferenceExists('party', (int) $payload['party_id'], $tenantId)) {
            return $this->invalid('Party owner references require an existing same-tenant party.');
        }

        $payload['vehicle_id'] = $vehicleId;
        $payload['tenant_id'] = $tenantId;
        $payload['ownership_type'] = $ownershipType;
        $payload['owner_type'] = $ownerType;
        $payload['ownership_role'] = $ownershipRole;
        $payload['is_current'] = (bool) ($payload['is_current'] ?? true);

        if (! array_key_exists('row_version', $payload)) {
            $payload['row_version'] = 1;
        }

        return $payload;
    }

    private function guardVehicleScope(int $tenantId, int $vehicleId): ?Result
    {
        if ($tenantId < 1 || $vehicleId < 1) {
            return $this->invalid('Tenant and vehicle are required.');
        }

        if (! $this->ownerships->vehicleExistsInTenant($tenantId, $vehicleId)) {
            return $this->notFound('Vehicle not found in tenant scope.');
        }

        return null;
    }

    private function isValidDate(string $value): bool
    {
        return DateTimeImmutable::createFromFormat('Y-m-d', $value) !== false;
    }

    private function dateIsBefore(string $left, string $right): bool
    {
        return new DateTimeImmutable($left) < new DateTimeImmutable($right);
    }

    private function invalid(string $message): Result
    {
        return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $message));
    }

    private function notFound(string $message): Result
    {
        return Result::failure(new Error(VehicleErrorCode::NOT_FOUND, $message));
    }
}
