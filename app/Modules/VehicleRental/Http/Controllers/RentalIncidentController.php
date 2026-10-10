<?php
declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\IncidentReviewAction;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Http\Resources\RentalIncidentResource;
use Modules\VehicleRental\Services\RentalIncidentService;
use Symfony\Component\HttpFoundation\Response;

final class RentalIncidentController
{
    public function __construct(private readonly RentalIncidentService $incidents) {}

    public function index(AgreementRequest $request): AnonymousResourceCollection
    {
        return RentalIncidentResource::collection($this->incidents->list($request->context(), $request->perPage()));
    }

    public function store(AgreementRequest $request): JsonResponse
    {
        $data = $request->only([
            'vehicle_use_id', 'running_chart_id', 'incident_type',
            'occurred_on', 'evidence_reference', 'description',
        ]);
        return (new RentalIncidentResource($this->incidents->create($request->context(), $data)))
            ->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(AgreementRequest $request, int $incident): RentalIncidentResource
    {
        return new RentalIncidentResource($this->incidents->find($request->context(), $incident));
    }

    public function history(AgreementRequest $request, int $incident): JsonResponse
    {
        return response()->json($this->incidents->history($request->context(), $incident, $request->perPage()));
    }

    public function review(AgreementRequest $request, int $incident, string $action): RentalIncidentResource
    {
        return new RentalIncidentResource($this->incidents->review(
            $request->context(),
            $incident,
            $request->expectedVersion(),
            IncidentReviewAction::from($action),
            (string) $request->input('reason', ''),
        ));
    }
}
