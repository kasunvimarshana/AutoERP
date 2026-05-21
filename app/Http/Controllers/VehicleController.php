<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\VehicleCollectionResource;
use App\Http\Resources\VehicleResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Vehicle\Infrastructure\Persistence\Eloquent\Models\VehicleModel;

class VehicleController extends Controller
{
    //
    public function __construct()
    {
        //
    }

    public function index(Request $request)
    {
        //
        $validated = $request->all();

        $pageName = config('core.pagination.page_name', 'page');
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated[$pageName] ?? 1);
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $columns = ['*'];

        $vehicles = VehicleModel::with(['suppliers', 'customers', 'currentSupplier', 'currentCustomer'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new VehicleCollectionResource($vehicles);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreVehicleRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $vehicle = VehicleModel::create($payload);

            // $vehicle->fresh();

            $vehicle->load(['suppliers', 'customers', 'currentSupplier', 'currentCustomer']);

            DB::commit();

            $resource = new VehicleResource($vehicle);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $vehicleId)
    {
        $vehicleEntity = VehicleModel::findOrFail($vehicleId);

        $vehicleEntity->load(['suppliers', 'customers', 'currentSupplier', 'currentCustomer']);

        $resource = new VehicleResource($vehicleEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateVehicleRequest $request, int $vehicleId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundVehicle = VehicleModel::findOrFail($vehicleId);

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $foundVehicle->update($payload);

            $foundVehicle->fresh();

            $foundVehicle->load(['suppliers', 'customers', 'currentSupplier', 'currentCustomer']);

            DB::commit();

            $resource = new VehicleResource($foundVehicle);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $vehicleId)
    {
        //
        $foundVehicle = VehicleModel::findOrFail($vehicleId);

        $foundVehicle->delete();
    }
}
