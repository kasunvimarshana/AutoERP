<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Services\UsageChargeBilling;
use Symfony\Component\HttpFoundation\Response;

final class UsageChargeBillingController
{
    public function __construct(private readonly UsageChargeBilling $billing) {}

    public function previewMileage(AgreementRequest $request, int $chart, string $kind): JsonResponse
    {
        return response()->json($this->billing->previewMileage(AgreementKind::from($kind), $request->context(), $chart));
    }

    public function assessMileage(AgreementRequest $request, int $chart, string $kind): JsonResponse
    {
        $result = $this->billing->assessMileage(AgreementKind::from($kind), $request->context(), $chart, $request->all());

        return response()->json(['assessment' => $result['assessment'], 'invoice' => $result['invoice'] === null ? null : new InvoiceResource($result['invoice'])], Response::HTTP_CREATED);
    }

    public function store(AgreementRequest $request, int $chart, string $kind): JsonResponse
    {
        return (new InvoiceResource($this->billing->create(AgreementKind::from($kind), $request->context(), $chart, $request->all())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function reissue(AgreementRequest $request, int $chart, string $kind, int $charge): JsonResponse
    {
        return (new InvoiceResource($this->billing->reissue(AgreementKind::from($kind), $request->context(), $chart, $charge, $request->all())))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function void(AgreementRequest $request, int $chart, string $kind, int $charge): JsonResponse
    {
        $this->billing->void(AgreementKind::from($kind), $request->context(), $chart, $charge, $request->all());

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function index(AgreementRequest $request, int $chart, string $kind): JsonResponse
    {
        return response()->json($this->billing->list(AgreementKind::from($kind), $request->context(), $chart, $request->perPage()));
    }
}
