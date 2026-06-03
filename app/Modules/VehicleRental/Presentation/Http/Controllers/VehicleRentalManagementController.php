<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\Services\VehicleRentalManagementServiceInterface;
use Modules\VehicleRental\Presentation\Http\Requests\ListVehicleRentalRunningChartRequest;
use Modules\VehicleRental\Presentation\Http\Requests\SyncVehicleRentalLinesRequest;
use Modules\VehicleRental\Presentation\Http\Requests\VehicleRentalAvailabilityRequest;
use Modules\VehicleRental\Presentation\Http\Requests\VehicleRentalBillingPreviewRequest;
use Modules\VehicleRental\Presentation\Http\Requests\VehicleRentalRecordRequest;
use Modules\VehicleRental\Presentation\Http\Requests\VehicleRentalSettingsRequest;
use Modules\VehicleRental\Presentation\Http\Requests\VehicleRentalStatusHistoryRequest;
use Modules\VehicleRental\Presentation\Http\Resources\VehicleRentalRecordResource;

final class VehicleRentalManagementController extends Controller
{
    public function __construct(private readonly VehicleRentalManagementServiceInterface $service) {}

    public function syncAgreementLines(SyncVehicleRentalLinesRequest $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->syncAgreementLines($agreementId, $request->all()));
    }

    public function syncAgreementRates(SyncVehicleRentalLinesRequest $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->syncAgreementRates($agreementId, $request->all()));
    }

    public function syncRateRules(SyncVehicleRentalLinesRequest $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->syncRateRules($agreementId, $request->all()));
    }

    public function syncRunningChartLines(SyncVehicleRentalLinesRequest $request, int $runningChartId): JsonResponse
    {
        return $this->respond($this->service->syncRunningChartLines($runningChartId, $request->all()));
    }

    public function syncExtraCharges(SyncVehicleRentalLinesRequest $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->syncExtraCharges($agreementId, $request->all()));
    }

    public function storeReplacement(VehicleRentalRecordRequest $request): JsonResponse
    {
        return $this->respond($this->service->upsertReplacement(null, $request->all()));
    }

    public function updateReplacement(VehicleRentalRecordRequest $request, int $replacementId): JsonResponse
    {
        return $this->respond($this->service->upsertReplacement($replacementId, $request->all()));
    }

    public function storeBreakdown(VehicleRentalRecordRequest $request): JsonResponse
    {
        return $this->respond($this->service->upsertBreakdown(null, $request->all()));
    }

    public function updateBreakdown(VehicleRentalRecordRequest $request, int $breakdownId): JsonResponse
    {
        return $this->respond($this->service->upsertBreakdown($breakdownId, $request->all()));
    }

    public function showSettings(VehicleRentalSettingsRequest $request): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 0);
        $organizationUnitId = $request->has('organization_unit_id')
            ? (int) $request->input('organization_unit_id')
            : null;

        return $this->respond($this->service->getSettings($tenantId, $organizationUnitId));
    }

    public function upsertSettings(VehicleRentalSettingsRequest $request): JsonResponse
    {
        return $this->respond($this->service->upsertSettings($request->all()));
    }

    public function initializeSettings(VehicleRentalSettingsRequest $request): JsonResponse
    {
        return $this->respond($this->service->initializeSettings($request->all()));
    }

    public function statusHistory(VehicleRentalStatusHistoryRequest $request, string $entityType, int $entityId): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 0);

        return $this->respond($this->service->getStatusHistory($entityType, $entityId, $tenantId));
    }

    public function vehicleAvailability(VehicleRentalAvailabilityRequest $request): JsonResponse
    {
        return $this->respond($this->service->getVehicleAvailability(
            (int) $request->input('tenant_id', 0),
            (int) $request->input('rental_vehicle_id', 0),
            (string) $request->input('start_datetime'),
            $request->input('end_datetime'),
            $request->has('exclude_agreement_id') ? (int) $request->input('exclude_agreement_id') : null,
        ));
    }

    public function billingPreview(VehicleRentalBillingPreviewRequest $request, int $agreementId): JsonResponse
    {
        return $this->respond($this->service->previewBilling($agreementId, $request->all()));
    }

    public function providerPayables(ListVehicleRentalRunningChartRequest $request): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 0);
        $agreementId = $request->has('agreement_id') ? (int) $request->input('agreement_id') : null;

        return $this->respond($this->service->listProviderPayables($tenantId, $agreementId));
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'VEHICLERENTAL_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message], $statusCode);
        }

        return response()->json(['data' => (new VehicleRentalRecordResource($result->valueOrFail()))->resolve()]);
    }
}
