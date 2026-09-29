<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use LogicException;
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

    public function civilDate(AgreementContext $context, DateTimeInterface $instant): string
    {
        return CarbonImmutable::instance($instant)
            ->setTimezone($this->timezone($context))
            ->toDateString();
    }

    public function coverageEnd(Agreement $agreement, AgreementContext $context): ?string
    {
        $contractEnd = $agreement->ends_on?->toDateString();
        if ($agreement->status !== AgreementStatus::Closed) {
            return $contractEnd;
        }
        if ($agreement->closed_on === null) {
            throw new LogicException('Closed rental agreement is missing its immutable closure date. Run the Vehicle Rental migrations before calculating charges.');
        }

        $closedOn = $agreement->closed_on->toDateString();

        return $contractEnd === null || $closedOn < $contractEnd ? $closedOn : $contractEnd;
    }
}
