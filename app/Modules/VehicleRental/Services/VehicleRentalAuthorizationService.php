<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;

final class VehicleRentalAuthorizationService
{
    public const VIEW = 'vehicle-rental.view';
    public const VIEW_FINANCIAL = 'vehicle-rental.financial.view';
    public const VIEW_PROFITABILITY = 'vehicle-rental.profitability.view';
    public const MANAGE_RESERVATIONS = 'vehicle-rental.reservations.manage';
    public const MANAGE_AGREEMENTS = 'vehicle-rental.agreements.manage';
    public const MANAGE_RATES = 'vehicle-rental.rates.manage';
    public const MANAGE_ALLOCATIONS = 'vehicle-rental.allocations.manage';
    public const MANAGE_CUSTODY = 'vehicle-rental.custody.manage';
    public const MANAGE_REPLACEMENTS = 'vehicle-rental.replacements.manage';
    public const RECORD_USAGE = 'vehicle-rental.usage.record';
    public const APPROVE_USAGE = 'vehicle-rental.usage.approve';
    public const RECORD_EXPENSES = 'vehicle-rental.expenses.record';
    public const APPROVE_EXPENSES = 'vehicle-rental.expenses.approve';
    public const CALCULATE = 'vehicle-rental.calculations.manage';
    public const APPROVE_CALCULATIONS = 'vehicle-rental.calculations.approve';
    public const CREATE_FINANCIAL_DOCUMENTS = 'vehicle-rental.financial.create';
    public const MANAGE_DEPOSITS = 'vehicle-rental.deposits.manage';
    public const MANAGE_FINANCE_AGREEMENTS = 'vehicle-rental.finance-agreements.manage';

    public function __construct(private readonly UserAccessResolver $access) {}

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->access->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Vehicle Rental action requires permission: '.$permission);
        }
    }
}
