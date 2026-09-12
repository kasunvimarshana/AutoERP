<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RunningChartAction;

class RentalAuthorization
{
    public const CUSTOMER_BILL = 'vehicle-rental.customer-agreements.bill';

    public const OWNER_BILL = 'vehicle-rental.owner-agreements.bill';

    public const CHART_VIEW = 'vehicle-rental.running-charts.view';

    public const CHART_MANAGE = 'vehicle-rental.running-charts.manage';

    public const CHART_FINALIZE = 'vehicle-rental.running-charts.finalize';

    public const CHART_REVERSE = 'vehicle-rental.running-charts.reverse';

    public const USE_VIEW = 'vehicle-rental.vehicle-use.view';

    public const USE_MANAGE = 'vehicle-rental.vehicle-use.manage';

    public const CUSTOMER_VIEW = 'vehicle-rental.customer-agreements.view';

    public const CUSTOMER_MANAGE = 'vehicle-rental.customer-agreements.manage';

    public const OWNER_VIEW = 'vehicle-rental.owner-agreements.view';

    public const OWNER_MANAGE = 'vehicle-rental.owner-agreements.manage';

    public function __construct(private readonly UserAccessResolver $access) {}

    public static function descriptions(): array
    {
        return [self::CUSTOMER_BILL => 'Create customer base-rent invoice drafts.', self::OWNER_BILL => 'Create owner base-rent payable drafts.', self::CHART_VIEW => 'View Running Chart evidence.', self::CHART_MANAGE => 'Create and edit draft Running Charts.', self::CHART_FINALIZE => 'Finalize physical usage evidence.', self::CHART_REVERSE => 'Reverse finalized physical usage evidence.', self::USE_VIEW => 'View assigned vehicles and custody history.', self::USE_MANAGE => 'Plan, hand over, return and cancel vehicle use.', self::CUSTOMER_VIEW => 'View customer rental agreements.', self::CUSTOMER_MANAGE => 'Create, edit drafts, activate and close customer rental agreements.', self::OWNER_VIEW => 'View owner rental agreements.', self::OWNER_MANAGE => 'Create, edit drafts, activate and close owner rental agreements.'];
    }

    public function assert(AgreementContext $context, AgreementKind $kind, bool $write): void
    {
        $permission = match ($kind) {
            AgreementKind::Customer => $write ? self::CUSTOMER_MANAGE : self::CUSTOMER_VIEW,
            AgreementKind::Owner => $write ? self::OWNER_MANAGE : self::OWNER_VIEW,
        };
        if (! $this->access->can($context->actorId, $context->tenantId, $permission)) {
            throw new AuthorizationException('This Rental action requires permission: '.$permission);
        }
    }

    public function assertBilling(AgreementContext $context, AgreementKind $kind): void
    {
        $permission = $kind === AgreementKind::Customer ? self::CUSTOMER_BILL : self::OWNER_BILL;
        if (! $this->access->can($context->actorId, $context->tenantId, $permission)) {
            throw new AuthorizationException('This Rental action requires permission: '.$permission);
        }
    }

    public function assertUse(AgreementContext $context, bool $write): void
    {
        $permission = $write ? self::USE_MANAGE : self::USE_VIEW;
        if (! $this->access->can($context->actorId, $context->tenantId, $permission)) {
            throw new AuthorizationException('This Rental action requires permission: '.$permission);
        }
    }

    public function assertChart(AgreementContext $context, RunningChartAction $action, bool $write): void
    {
        $permission = ! $write ? self::CHART_VIEW : match ($action) {
            RunningChartAction::Finalize => self::CHART_FINALIZE,
            RunningChartAction::Reverse => self::CHART_REVERSE,
            default => self::CHART_MANAGE,
        };
        if (! $this->access->can($context->actorId, $context->tenantId, $permission)) {
            throw new AuthorizationException('This Rental action requires permission: '.$permission);
        }
    }
}
