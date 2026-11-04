<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\IncidentStatus;
use Modules\VehicleRental\Models\RentalIncident;

final class RentalIncidentReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{start_date: string, end_date: string, incident_type?: ?string, status?: ?string, vehicle_id?: ?int}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertReport($context);

        if (empty($params['start_date']) || empty($params['end_date'])) {
            throw ValidationException::withMessages(['start_date' => ['Specify both start date and end date for the incident report.']]);
        }

        $startDate = (string) $params['start_date'];
        $endDate = (string) $params['end_date'];

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
        }

        $typeFilter = ! empty($params['incident_type']) ? (string) $params['incident_type'] : null;
        $statusFilter = ! empty($params['status']) ? (string) $params['status'] : null;
        $vehicleId = ! empty($params['vehicle_id']) ? (int) $params['vehicle_id'] : null;

        $incidents = RentalIncident::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['vehicleUse.vehicle', 'vehicleUse.customerAgreement.customer', 'runningChart', 'attachments'])
            ->where('occurred_on', '>=', $startDate)
            ->where('occurred_on', '<=', $endDate)
            ->when($typeFilter !== null, fn (Builder $q) => $q->where('incident_type', $typeFilter))
            ->when($statusFilter !== null, fn (Builder $q) => $q->where('status', $statusFilter))
            ->when($vehicleId !== null, fn (Builder $q) => $q->whereHas('vehicleUse', fn (Builder $u) => $u->where('vehicle_id', $vehicleId)))
            ->orderByDesc('occurred_on')
            ->orderBy('reference')
            ->get();

        $rows = [];
        $typeCounts = [];
        $recordedCount = 0;
        $confirmedCount = 0;
        $rejectedCount = 0;

        foreach ($incidents as $incident) {
            $use = $incident->vehicleUse;
            $vehicleNo = $use?->vehicle_label_snapshot
                ?? $use?->vehicle?->registration_number
                ?? $use?->vehicle?->vehicle_number
                ?? 'N/A';

            $customerName = $use?->customerAgreement?->customer?->name
                ?? $use?->customerAgreement?->party_name_snapshot
                ?? 'N/A';

            $typeLabel = $incident->incident_type->label();
            $statusLabel = $incident->status->label();

            $typeCounts[$typeLabel] = ($typeCounts[$typeLabel] ?? 0) + 1;

            if ($incident->status === IncidentStatus::Recorded) {
                $recordedCount++;
            } elseif ($incident->status === IncidentStatus::Confirmed) {
                $confirmedCount++;
            } elseif ($incident->status === IncidentStatus::Rejected) {
                $rejectedCount++;
            }

            $rows[] = [
                'incident_id' => $incident->id,
                'reference' => $incident->reference,
                'occurred_on' => $incident->occurred_on->format('d/m/Y'),
                'vehicle_number' => $vehicleNo,
                'customer_name' => $customerName,
                'agreement_reference' => $use?->customerAgreement?->reference ?? '-',
                'incident_type' => $typeLabel,
                'status' => $statusLabel,
                'evidence_reference' => $incident->evidence_reference,
                'description' => $incident->description,
                'attachment_count' => $incident->attachments->count(),
                'reviewed_at' => $incident->reviewed_at?->format('d/m/Y H:i') ?? '-',
            ];
        }

        return [
            'header' => [
                'company_name' => config('app.name') ?: 'AutoERP',
                'report_title' => 'RENTAL INCIDENT & DAMAGE RISK REPORT',
                'duration' => $start->format('d/m/Y').' To '.$end->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'report_date' => now()->format('d/m/Y'),
            ],
            'filters' => [
                'incident_type' => $typeFilter,
                'status' => $statusFilter,
                'vehicle_id' => $vehicleId,
            ],
            'summary' => [
                'total_incidents' => count($rows),
                'recorded_count' => $recordedCount,
                'confirmed_count' => $confirmedCount,
                'rejected_count' => $rejectedCount,
                'type_breakdown' => $typeCounts,
            ],
            'rows' => $rows,
            'footer' => [
                'software_provider' => 'Software Provided by : AutoERP Computer Systems',
            ],
        ];
    }
}
