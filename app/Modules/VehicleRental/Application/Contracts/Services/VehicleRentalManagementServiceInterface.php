<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface VehicleRentalManagementServiceInterface
{
    public function listAgreements(int $tenantId, ?string $agreementRole = null): Result;

    public function listRentalVehicles(int $tenantId): Result;

    public function getAgreement(int $agreementId): Result;

    public function upsertAgreementAggregate(?int $id, array $payload): Result;

    public function syncAgreementLines(int $agreementId, array $payload): Result;

    public function syncAgreementRates(int $agreementId, array $payload): Result;

    public function syncRateRules(int $agreementId, array $payload): Result;

    public function listRunningCharts(int $tenantId, ?int $agreementId = null): Result;

    public function getRunningChart(int $runningChartId): Result;

    public function upsertRunningChartAggregate(?int $id, array $payload): Result;

    public function syncRunningChartLines(int $runningChartId, array $payload): Result;

    public function syncExtraCharges(int $agreementId, array $payload): Result;

    public function upsertReplacement(?int $id, array $payload): Result;

    public function upsertBreakdown(?int $id, array $payload): Result;

    public function getStatusHistory(string $entityType, int $entityId, int $tenantId): Result;

    public function getSettings(int $tenantId, ?int $organizationUnitId): Result;

    public function upsertSettings(array $payload): Result;

    public function initializeSettings(array $payload): Result;

    public function getVehicleAvailability(
        int $tenantId,
        int $rentalVehicleId,
        string $startDateTime,
        ?string $endDateTime,
        ?int $excludeAgreementId = null,
    ): Result;

    public function previewBilling(int $agreementId, array $payload): Result;

    public function listProviderPayables(int $tenantId, ?int $agreementId = null): Result;
}
