<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;

class RentalAuthorization
{
    public const CUSTOMER_VIEW = 'vehicle-rental.customer-agreements.view';

    public const CUSTOMER_MANAGE = 'vehicle-rental.customer-agreements.manage';

    public const OWNER_VIEW = 'vehicle-rental.owner-agreements.view';

    public const OWNER_MANAGE = 'vehicle-rental.owner-agreements.manage';

    public function __construct(private readonly UserAccessResolver $access) {}

    public static function descriptions(): array
    {
        return [self::CUSTOMER_VIEW => 'View customer rental agreements.', self::CUSTOMER_MANAGE => 'Create, edit drafts, activate and close customer rental agreements.', self::OWNER_VIEW => 'View owner rental agreements.', self::OWNER_MANAGE => 'Create, edit drafts, activate and close owner rental agreements.'];
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
}
