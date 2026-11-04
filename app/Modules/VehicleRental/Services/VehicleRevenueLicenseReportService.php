<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Services\DecimalMath;
use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Models\OwnerAgreement;

final class VehicleRevenueLicenseReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{start_date?: ?string, end_date?: ?string, status?: ?string, vehicle_id?: ?int}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertReport($context);

        $startDate = ! empty($params['start_date']) ? (string) $params['start_date'] : null;
        $endDate = ! empty($params['end_date']) ? (string) $params['end_date'] : null;
        $statusFilter = (string) ($params['status'] ?? 'all');
        $selectedVehicleId = ! empty($params['vehicle_id']) ? (int) $params['vehicle_id'] : null;

        $today = CarbonImmutable::today();

        // Query fleet vehicles with documents
        $vehicles = Vehicle::query()
            ->where('tenant_id', $context->tenantId)
            ->when($selectedVehicleId !== null, fn (Builder $q) => $q->where('id', $selectedVehicleId))
            ->with(['documents' => function ($docQuery) {
                $docQuery->where('document_type', VehicleDocumentType::RevenueLicense->value)
                    ->orderByDesc('expiry_date');
            }])
            ->orderBy('vehicle_number')
            ->get();

        // Query active owner agreements to identify owner/lessor
        $ownerAgreements = OwnerAgreement::query()
            ->forTenant($context->tenantId)
            ->with('party')
            ->whereIn('status', [AgreementStatus::Active->value, AgreementStatus::Closed->value])
            ->orderByDesc('starts_on')
            ->get()
            ->groupBy('vehicle_id');

        $rows = [];
        $expiredCount = 0;
        $expiringSoonCount = 0;
        $activeCount = 0;

        foreach ($vehicles as $vehicle) {
            $regNumber = $vehicle->registration_number ?? $vehicle->vehicle_number;
            $ownerAgreement = $ownerAgreements->get($vehicle->id)?->first();
            $ownerName = $ownerAgreement?->party?->name ?? '';
            $ownershipType = $ownerAgreement !== null ? 'Owner Leased' : 'Company Owned';

            $doc = $vehicle->documents->first();
            $rawExpiry = $doc?->expiry_date?->toDateString()
                ?? ($vehicle->metadata['revenue_license_expiry'] ?? null);

            $expiryDate = $rawExpiry !== null ? CarbonImmutable::parse($rawExpiry) : null;

            if ($startDate !== null && $expiryDate !== null && $expiryDate->lt(CarbonImmutable::parse($startDate))) {
                continue;
            }
            if ($endDate !== null && $expiryDate !== null && $expiryDate->gt(CarbonImmutable::parse($endDate))) {
                continue;
            }

            if ($expiryDate === null) {
                $status = 'missing';
                $statusLabel = 'Missing License';
                $daysRemaining = null;
            } elseif ($expiryDate->lt($today)) {
                $status = 'expired';
                $statusLabel = 'Expired';
                $daysRemaining = (int) $today->diffInDays($expiryDate, false);
                $expiredCount++;
            } elseif ($expiryDate->diffInDays($today) <= 30) {
                $status = 'expiring_soon';
                $statusLabel = 'Expiring Soon';
                $daysRemaining = (int) $today->diffInDays($expiryDate);
                $expiringSoonCount++;
            } else {
                $status = 'active';
                $statusLabel = 'Valid';
                $daysRemaining = (int) $today->diffInDays($expiryDate);
                $activeCount++;
            }

            if ($statusFilter !== 'all' && $statusFilter !== $status) {
                continue;
            }

            $rows[] = [
                'vehicle_id' => $vehicle->id,
                'vehicle_no' => $regNumber,
                'owner_name' => $ownerName,
                'ownership_type' => $ownershipType,
                'expire_date' => $expiryDate?->format('d/m/Y') ?? 'N/A',
                'raw_expire_date' => $expiryDate?->toDateString(),
                'days_remaining' => $daysRemaining,
                'document_number' => $doc?->document_number ?? '-',
                'status' => $status,
                'status_label' => $statusLabel,
            ];
        }

        $periodDescription = ($startDate && $endDate)
            ? "For The Period From {$startDate} To {$endDate} - All Vehicle"
            : 'As of '.now()->format('d/m/Y').' - All Vehicle';

        return [
            'header' => [
                'company_name' => 'AT Tours & Transport (Pvt) Ltd',
                'system_title' => 'INTEGRATED RENT A CAR SYSTEM',
                'report_title' => 'Vehicle Revenue Licence Expire Report',
                'period_description' => $periodDescription,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'generated_at' => now()->toIso8601String(),
            ],
            'summary' => [
                'total_vehicles' => count($rows),
                'expired_count' => $expiredCount,
                'expiring_soon_count' => $expiringSoonCount,
                'active_count' => $activeCount,
            ],
            'data' => $rows,
        ];
    }
}
