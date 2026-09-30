<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleService\Http\Requests\ListVehicleServiceJobRequest;
use Modules\VehicleService\Http\Requests\RemoveVehicleServiceJobDiscountRequest;
use Modules\VehicleService\Http\Requests\StoreVehicleServiceJobDiscountRequest;
use Modules\VehicleService\Http\Resources\VehicleServiceJobDiscountResource;
use Modules\VehicleService\Http\Resources\VehicleServiceJobResource;
use Modules\VehicleService\Services\VehicleServiceJobDiscountService;
use Modules\VehicleService\Services\VehicleServiceJobService;

final class VehicleServiceJobDiscountController extends VehicleServiceController
{
    public function history(ListVehicleServiceJobRequest $request, int $job): AnonymousResourceCollection
    {
        return VehicleServiceJobDiscountResource::collection(
            $this->job($request, $job)->discountRevisions()->with('changedBy')->get(),
        );
    }

    public function update(
        StoreVehicleServiceJobDiscountRequest $request,
        int $job,
        VehicleServiceJobDiscountService $discounts,
        VehicleServiceJobService $jobs,
    ): VehicleServiceJobResource {
        $updated = $discounts->set(
            $this->job($request, $job),
            $request->toData(),
            $request->expectedVersion(),
        );

        return new VehicleServiceJobResource($updated->load($jobs->relations()));
    }

    public function destroy(
        RemoveVehicleServiceJobDiscountRequest $request,
        int $job,
        VehicleServiceJobDiscountService $discounts,
        VehicleServiceJobService $jobs,
    ): VehicleServiceJobResource {
        $updated = $discounts->remove(
            $this->job($request, $job),
            $request->reason(),
            $request->changedBy(),
            $request->expectedVersion(),
        );

        return new VehicleServiceJobResource($updated->load($jobs->relations()));
    }
}
