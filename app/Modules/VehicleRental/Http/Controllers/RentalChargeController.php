<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Resources\RentalChargeResource;
use Modules\VehicleRental\Services\RentalChargeCalculationService;
use Modules\VehicleRental\Services\RentalChargeService;
use Modules\VehicleRental\Services\RentalInvoiceIntegrationService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalChargeController extends RentalController
{
    public function index(
        ListRentalRequest $request,
        int $agreement,
        RentalInvoiceIntegrationService $invoices,
    ): AnonymousResourceCollection {
        return RentalChargeResource::collection(
            $invoices->billableCharges($this->agreement($request, $agreement)),
        );
    }

    public function generate(
        RentalActionRequest $request,
        int $agreement,
        RentalChargeCalculationService $service,
        VehicleRentalAuthorizationService $authorization,
    ): AnonymousResourceCollection {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::GENERATE_CHARGES,
        );

        return RentalChargeResource::collection($service->calculate(
            $this->agreement($request, $agreement),
            $request->boolean('replace'),
        ));
    }

    public function preview(
        ListRentalRequest $request,
        int $agreement,
        RentalChargeCalculationService $service,
    ): AnonymousResourceCollection {
        return RentalChargeResource::collection(
            $service->preview($this->agreement($request, $agreement)),
        );
    }

    public function approveAll(
        RentalActionRequest $request,
        int $agreement,
        RentalChargeService $service,
        VehicleRentalAuthorizationService $authorization,
    ): AnonymousResourceCollection {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::APPROVE_CHARGES,
        );

        return RentalChargeResource::collection(
            $service->approveAgreementCharges(
                $this->agreement($request, $agreement),
                $request->currentUserId(),
                $request->input('reason'),
            ),
        );
    }
}
