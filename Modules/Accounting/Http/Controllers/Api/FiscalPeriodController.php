<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Entities\FiscalPeriod;
use Modules\Accounting\Events\FiscalPeriodClosed;
use Modules\Accounting\Http\Requests\StoreFiscalPeriodRequest;
use Modules\Accounting\Http\Requests\UpdateFiscalPeriodRequest;
use Modules\Accounting\Http\Resources\FiscalPeriodResource;
use Modules\Accounting\Repositories\Contracts\FiscalPeriodRepositoryInterface;

class FiscalPeriodController extends Controller
{
    public function __construct(
        protected FiscalPeriodRepositoryInterface $fiscalPeriodRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $periods = $this->fiscalPeriodRepository->paginate($perPage);

        return FiscalPeriodResource::collection($periods)->response();
    }

    public function store(StoreFiscalPeriodRequest $request): JsonResponse
    {
        $period = $this->fiscalPeriodRepository->create($request->validated());

        return (new FiscalPeriodResource($period))->response()->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $period = $this->fiscalPeriodRepository->findById($id);
        if (! $period) {
            return response()->json(['message' => 'Fiscal period not found'], 404);
        }

        return (new FiscalPeriodResource($period))->response();
    }

    public function update(UpdateFiscalPeriodRequest $request, int $id): JsonResponse
    {
        $period = $this->fiscalPeriodRepository->update($id, $request->validated());

        return (new FiscalPeriodResource($period))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->fiscalPeriodRepository->delete($id);

        return response()->json(null, 204);
    }

    public function open(int $id): JsonResponse
    {
        $period = $this->fiscalPeriodRepository->findById($id);
        if (! $period) {
            return response()->json(['message' => 'Fiscal period not found'], 404);
        }
        $period->status = FiscalPeriod::STATUS_OPEN;
        $period->save();

        return (new FiscalPeriodResource($period))->response();
    }

    public function close(int $id): JsonResponse
    {
        $period = $this->fiscalPeriodRepository->findById($id);
        if (! $period) {
            return response()->json(['message' => 'Fiscal period not found'], 404);
        }
        if ($period->isClosed()) {
            return response()->json(['message' => 'Period already closed'], 400);
        }
        $period->status = FiscalPeriod::STATUS_CLOSED;
        $period->closed_at = now();
        $period->closed_by = Auth::id();
        $period->save();
        event(new FiscalPeriodClosed($period));

        return (new FiscalPeriodResource($period))->response();
    }

    public function current(): JsonResponse
    {
        $period = $this->fiscalPeriodRepository->getCurrentPeriod();
        if (! $period) {
            return response()->json(['message' => 'No current period found'], 404);
        }

        return (new FiscalPeriodResource($period))->response();
    }
}
