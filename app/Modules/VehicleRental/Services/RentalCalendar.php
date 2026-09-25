<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\VehicleRental\Constants\RentalConfiguration;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Models\Agreement;

final class RentalCalendar
{
    public function __construct(private readonly ConfigurationResolverInterface $configuration) {}

    public function timezone(AgreementContext $context): string
    {
        return (string) $this->configuration->value(
            RentalConfiguration::WORKSPACE_TIMEZONE,
            $context->tenantId,
            $context->organizationUnitId,
        );
    }

    public function today(AgreementContext $context): CarbonImmutable
    {
        return CarbonImmutable::today($this->timezone($context));
    }

    public function coverageEnd(Agreement $agreement, AgreementContext $context, ?string $timezone = null): ?string
    {
        $contractEnd = $agreement->ends_on?->toDateString();
        if ($agreement->status !== AgreementStatus::Closed || $agreement->closed_at === null) {
            return $contractEnd;
        }

        $closedOn = $agreement->closed_at
            ->setTimezone($timezone ?? $this->timezone($context))
            ->toDateString();

        return $contractEnd === null || $closedOn < $contractEnd ? $closedOn : $contractEnd;
    }
}
