<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use Modules\VehicleRental\Constants\VehicleRentalSource;
use RuntimeException;

final class RentalNumberService
{
    private const PERIOD_TYPE = 'infinite';
    private const PADDING = 6;

    public function __construct(private readonly GenerateSequenceNumberService $sequences) {}

    public function agreement(int $tenantId, ?int $organizationUnitId): string
    {
        return $this->generate(
            $tenantId,
            $organizationUnitId,
            VehicleRentalSource::AGREEMENT_DOCUMENT,
            (string) config('vehicle-rental.agreement_number_prefix', 'VRA').'-',
        );
    }

    public function runningChart(int $tenantId, ?int $organizationUnitId): string
    {
        return $this->generate(
            $tenantId,
            $organizationUnitId,
            VehicleRentalSource::RUNNING_CHART_DOCUMENT,
            (string) config('vehicle-rental.running_chart_number_prefix', 'VRC').'-',
        );
    }

    public function calculation(int $tenantId, ?int $organizationUnitId): string
    {
        return $this->generate(
            $tenantId,
            $organizationUnitId,
            VehicleRentalSource::CALCULATION_DOCUMENT,
            (string) config('vehicle-rental.calculation_number_prefix', 'VRCAL').'-',
        );
    }

    private function generate(int $tenantId, ?int $organizationUnitId, string $documentType, string $prefix): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'document_type' => $documentType,
            'period_type' => self::PERIOD_TYPE,
            'prefix' => $prefix,
            'padding' => self::PADDING,
        ]);

        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }
}
