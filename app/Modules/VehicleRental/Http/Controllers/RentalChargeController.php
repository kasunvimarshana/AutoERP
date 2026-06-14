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
    ): AnonymousResourceCollection {
        return RentalChargeResource::collection($service->calculate(
            $this->agreement($request, $agreement),
            $request->boolean('replace'),
        ));
    }

    public function approveAll(
        RentalActionRequest $request,
        int $agreement,
        RentalChargeService $service,
    ): AnonymousResourceCollection {
        return RentalChargeResource::collection(
            $service->approveAgreementCharges($this->agreement($request, $agreement)),
        );
    }
}
