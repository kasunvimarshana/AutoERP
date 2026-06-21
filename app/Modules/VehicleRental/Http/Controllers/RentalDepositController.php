<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Models\Invoice;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\ApplyRentalDepositRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalDepositPaymentRequest;
use Modules\VehicleRental\Http\Resources\RentalDepositRequirementResource;
use Modules\VehicleRental\Models\RentalDepositLink;
use Modules\VehicleRental\Models\RentalDepositRequirement;
use Modules\VehicleRental\Services\RentalDepositService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalDepositController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW_FINANCIAL);
        $query = $this->scope(RentalDepositRequirement::query(), $request)->with(['agreement.customer', 'currency', 'links.payment', 'links.invoice']);
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('agreement_id')) $query->where('agreement_id', $request->input('agreement_id'));
        return RentalDepositRequirementResource::collection($query->latest('id')->paginate($request->perPage()));
    }

    public function show(ListRentalRequest $request, int $deposit): RentalDepositRequirementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW_FINANCIAL);
        return new RentalDepositRequirementResource($this->scope(RentalDepositRequirement::query(), $request)->with(['agreement.customer', 'currency', 'links.payment', 'links.invoice'])->findOrFail($deposit));
    }

    public function receive(RentalDepositPaymentRequest $request, int $deposit, RentalDepositService $service): RentalDepositRequirementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_DEPOSITS);
        return new RentalDepositRequirementResource($service->receive($this->scope(RentalDepositRequirement::query(), $request)->findOrFail($deposit), $request->validated(), $request->currentUserId()));
    }

    public function apply(ApplyRentalDepositRequest $request, int $deposit, RentalDepositService $service): RentalDepositRequirementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_DEPOSITS);
        $requirement = $this->scope(RentalDepositRequirement::query(), $request)->findOrFail($deposit);
        $invoice = $this->scope(Invoice::query(), $request)->findOrFail((int) $request->input('invoice_id'));
        return new RentalDepositRequirementResource($service->applyToInvoice($requirement, $invoice, (string) $request->input('amount'), $request->currentUserId()));
    }

    public function refund(RentalDepositPaymentRequest $request, int $deposit, RentalDepositService $service): RentalDepositRequirementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_DEPOSITS);
        return new RentalDepositRequirementResource($service->refund($this->scope(RentalDepositRequirement::query(), $request)->findOrFail($deposit), $request->validated(), $request->currentUserId()));
    }

    public function forfeit(ApplyRentalDepositRequest $request, int $deposit, RentalDepositService $service): RentalDepositRequirementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_DEPOSITS);
        $requirement = $this->scope(RentalDepositRequirement::query(), $request)->findOrFail($deposit);
        $invoice = $this->scope(Invoice::query(), $request)->findOrFail((int) $request->input('invoice_id'));
        return new RentalDepositRequirementResource($service->forfeit($requirement, $invoice, (string) $request->input('amount'), $request->currentUserId()));
    }

    public function reverse(ListRentalRequest $request, int $link, RentalDepositService $service): RentalDepositRequirementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_DEPOSITS);
        return new RentalDepositRequirementResource($service->reverseLink($this->scope(RentalDepositLink::query(), $request)->findOrFail($link), $request->currentUserId()));
    }
}
