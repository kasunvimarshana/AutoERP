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

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            VehicleRentalAuthorizationService::VIEW => 'View Vehicle Rental operational records.',
            VehicleRentalAuthorizationService::VIEW_FINANCIAL => 'View Vehicle Rental financial records.',
            VehicleRentalAuthorizationService::VIEW_PROFITABILITY => 'View Vehicle Rental profitability.',
            VehicleRentalAuthorizationService::MANAGE_RESERVATIONS => 'Create and progress rental reservations.',
            VehicleRentalAuthorizationService::MANAGE_AGREEMENTS => 'Create and progress customer and owner rental agreements.',
            VehicleRentalAuthorizationService::MANAGE_RATES => 'Create and activate immutable rental rate versions.',
            VehicleRentalAuthorizationService::MANAGE_ALLOCATIONS => 'Allocate vehicles and drivers.',
            VehicleRentalAuthorizationService::MANAGE_CUSTODY => 'Record and confirm rental custody handovers and returns.',
            VehicleRentalAuthorizationService::MANAGE_REPLACEMENTS => 'Replace active rental vehicles atomically.',
            VehicleRentalAuthorizationService::RECORD_USAGE => 'Create and submit rental running charts.',
            VehicleRentalAuthorizationService::APPROVE_USAGE => 'Approve, reject, or reverse rental running charts.',
            VehicleRentalAuthorizationService::RECORD_EXPENSES => 'Create and submit rental expenses.',
            VehicleRentalAuthorizationService::APPROVE_EXPENSES => 'Approve, reject, or reverse rental expenses.',
            VehicleRentalAuthorizationService::CALCULATE => 'Generate and submit rental revenue and owner cost calculations.',
            VehicleRentalAuthorizationService::APPROVE_CALCULATIONS => 'Approve or reverse rental calculations.',
            VehicleRentalAuthorizationService::CREATE_FINANCIAL_DOCUMENTS => 'Create rental invoices, payables, and finance installment payables.',
            VehicleRentalAuthorizationService::MANAGE_DEPOSITS => 'Receive, apply, refund, forfeit, and reverse rental deposits.',
            VehicleRentalAuthorizationService::MANAGE_FINANCE_AGREEMENTS => 'Manage vehicle lease and finance agreements.',
        ];
    }

    public function assert(?int $userId, int $tenantId, string $permission): void
    {
        if ($userId === null || ! $this->can($userId, $tenantId, $permission)) {
            throw new AuthorizationException('This Vehicle Rental action requires permission: ' . $permission);
        }
    }

    public function can(int $userId, int $tenantId, string $permission): bool
    {
        return $this->access->can($userId, $tenantId, $permission);
    }
}
