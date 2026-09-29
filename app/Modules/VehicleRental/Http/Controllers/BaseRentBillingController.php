<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Services\BaseRentBilling;
use Symfony\Component\HttpFoundation\Response;

final class BaseRentBillingController
{
    public function __construct(private readonly BaseRentBilling $billing) {}

    public function store(AgreementRequest $request, string $kind, int $agreement): JsonResponse
    {
        return (new InvoiceResource($this->billing->create(AgreementKind::from($kind), $request->context(), $agreement, $request->all())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function reissue(AgreementRequest $request, string $kind, int $agreement, int $charge): JsonResponse
    {
        return (new InvoiceResource($this->billing->reissue(AgreementKind::from($kind), $request->context(), $agreement, $charge, $request->all())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function void(AgreementRequest $request, string $kind, int $agreement, int $charge): JsonResponse
    {
        $this->billing->void(AgreementKind::from($kind), $request->context(), $agreement, $charge, $request->all());

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function index(AgreementRequest $request, string $kind, int $agreement): JsonResponse
    {
        return response()->json($this->billing->list(AgreementKind::from($kind), $request->context(), $agreement, $request->perPage()));
    }
}
