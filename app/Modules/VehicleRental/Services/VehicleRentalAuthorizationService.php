<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class VehicleRentalAuthorizationService
{
    public const MANAGE_RESERVATIONS = 'vehicle-rental.reservations.manage';

    public const MANAGE_AGREEMENTS = 'vehicle-rental.agreements.manage';

    public const MANAGE_ALLOCATIONS = 'vehicle-rental.allocations.manage';

    public const RECORD_INSPECTIONS = 'vehicle-rental.inspections.record';

    public const MANAGE_LINKS = 'vehicle-rental.links.manage';

    public const APPROVE_LINKS = 'vehicle-rental.links.approve';

    public const APPROVE_USAGE = 'vehicle-rental.usage.approve';

    public const RECORD_USAGE = 'vehicle-rental.usage.record';

    public const OVERRIDE_MILEAGE = 'vehicle-rental.usage.mileage-override';

    public const CLASSIFY_HOLIDAY = 'vehicle-rental.usage.classify-holiday';

    public const APPROVE_EXPENSES = 'vehicle-rental.expenses.approve';

    public const RECORD_EXPENSES = 'vehicle-rental.expenses.record';

    public const GENERATE_CHARGES = 'vehicle-rental.charges.generate';

    public const APPROVE_CHARGES = 'vehicle-rental.charges.approve';

    public const CREATE_FINANCIAL_DOCUMENTS = 'vehicle-rental.financial.create';

    public function __construct(private readonly UserAccessResolver $access) {}

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This VehicleRental action requires permission: '.$permission);
        }
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        return $this->access->can($userId, $tenantId, $permission);
    }
}
