<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Constants\RunningChartFields;
use Modules\VehicleRental\Enums\RunningChartAction;
use Modules\VehicleRental\Http\Requests\AgreementRequest;
use Modules\VehicleRental\Http\Resources\RunningChartHistoryResource;
use Modules\VehicleRental\Http\Resources\RunningChartRegisterResource;
use Modules\VehicleRental\Http\Resources\RunningChartResource;
use Modules\VehicleRental\Services\RunningChartRegisterService;
use Modules\VehicleRental\Services\RunningChartService;
use Symfony\Component\HttpFoundation\Response;

final class RunningChartController
{
    public function __construct(private readonly RunningChartService $charts) {}

    public function register(AgreementRequest $request, RunningChartRegisterService $register): AnonymousResourceCollection
    {
        return RunningChartRegisterResource::collection($register->list($request->context(), $request->only(['search', 'chart_status', 'from', 'until']), $request->perPage()));
    }

    public function index(AgreementRequest $request, int $use): AnonymousResourceCollection
    {
        return RunningChartResource::collection($this->charts->list($request->context(), $use, $request->perPage()));
    }

    public function store(AgreementRequest $request, int $use): JsonResponse
    {
        $data = $request->validate(['corrects_chart_id' => ['nullable', 'integer', 'min:1']]);

        return (new RunningChartResource($this->charts->create($request->context(), $use, $request->expectedVersion(), $request->only(RunningChartFields::MUTABLE), isset($data['corrects_chart_id']) ? (int) $data['corrects_chart_id'] : null)))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(AgreementRequest $request, int $chart): RunningChartResource
    {
        return new RunningChartResource($this->charts->change($request->context(), $chart, $request->expectedVersion(), RunningChartAction::Update, $request->only(RunningChartFields::MUTABLE)));
    }

    public function transition(AgreementRequest $request, int $chart, string $action): RunningChartResource
    {
        return new RunningChartResource($this->charts->change($request->context(), $chart, $request->expectedVersion(), RunningChartAction::from($action), reason: $request->input('reason')));
    }

    public function history(AgreementRequest $request, int $chart): AnonymousResourceCollection
    {
        return RunningChartHistoryResource::collection($this->charts->find($request->context(), $chart)->history()->with('actor')->paginate($request->perPage()));
    }
}
