<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceLineRequest;
use Modules\VehicleService\Http\Requests\VehicleServiceActionRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceJobLineResource;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Services\VehicleServiceAssignableLineService;
use Modules\VehicleService\Services\VehicleServiceLineService;

final class VehicleServiceLineController extends VehicleServiceController
{
    public function index(ListVehicleServiceJobRequest $request, int $job): AnonymousResourceCollection
    {
        return VehicleServiceJobLineResource::collection(
            $this->job($request, $job)->lines()
                ->whereNull('parent_line_id')
                ->with([
                    'item',
                    'variant',
                    'uom',
                    'children.item',
                    'children.uom',
                    'employeeAssignments.employee',
                ])
                ->get(),
        );
    }

    public function store(
        StoreVehicleServiceLineRequest $request,
        int $job,
        VehicleServiceLineService $service,
        VehicleServiceAssignableLineService $assignableLines,
    ): JsonResponse {
        $jobModel = $this->job($request, $job);
        $line = $service->create($jobModel, $request->toData(), $request->expectedVersion());

        return $this->mutationResponse($request, $jobModel, $line, $assignableLines, 201);
    }

    public function update(
        StoreVehicleServiceLineRequest $request,
        int $job,
        int $line,
        VehicleServiceLineService $service,
        VehicleServiceAssignableLineService $assignableLines,
    ): JsonResponse {
        $jobModel = $this->job($request, $job);
        $updatedLine = $service->update(
            $jobModel,
            $this->line($jobModel, $line),
            $request->toData(),
            $request->expectedVersion(),
        );

        return $this->mutationResponse($request, $jobModel, $updatedLine, $assignableLines);
    }

    public function destroy(
        VehicleServiceActionRequest $request,
        int $job,
        int $line,
        VehicleServiceLineService $service,
        VehicleServiceAssignableLineService $assignableLines,
    ): JsonResponse {
        $jobModel = $this->job($request, $job);
        $service->delete($jobModel, $this->line($jobModel, $line), $request->expectedVersion());

        return $this->mutationResponse($request, $jobModel, null, $assignableLines);
    }

    private function mutationResponse(
        StoreVehicleServiceLineRequest|VehicleServiceActionRequest $request,
        VehicleServiceJob $job,
        ?VehicleServiceJobLine $line,
        VehicleServiceAssignableLineService $assignableLines,
        int $status = 200,
    ): JsonResponse {
        $job->refresh();

        return response()->json([
            'data' => $line === null ? null : (new VehicleServiceJobLineResource($line))->resolve($request),
            'meta' => [
                'row_version' => (int) $job->row_version,
                'job_totals' => [
                    'subtotal' => (string) $job->subtotal,
                    'line_discount_total' => (string) $job->line_discount_total,
                    'job_discount_base' => (string) $job->job_discount_base,
                    'job_discount_amount' => (string) $job->job_discount_amount,
                    'discount_total' => (string) $job->discount_total,
                    'tax_total' => (string) $job->tax_total,
                    'charge_total' => (string) $job->charge_total,
                    'grand_total' => (string) $job->grand_total,
                    'commission_cost_total' => (string) $job->commission_cost_total,
                    'net_after_commission' => (string) $job->net_after_commission,
                ],
                'workforce_lines' => VehicleServiceJobLineResource::collection(
                    $assignableLines->forJob($job),
                )->resolve($request),
            ],
        ], $status);
    }
}
