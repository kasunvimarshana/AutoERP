<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDesignationRequest;
use App\Http\Requests\UpdateDesignationRequest;
use App\Http\Resources\DesignationCollectionResource;
use App\Http\Resources\DesignationResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DesignationModel;

class DesignationController extends Controller
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

        $designations = DesignationModel::paginate($perPage, $columns, $pageName, $page);

        $resource = new DesignationCollectionResource($designations);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreDesignationRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $designation = DesignationModel::create($payload);

            // $designation->fresh();

            DB::commit();

            $resource = new DesignationResource($designation);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $designationId)
    {
        $designationEntity = DesignationModel::findOrFail($designationId);

        $resource = new DesignationResource($designationEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateDesignationRequest $request, int $designationId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundDesignation = DesignationModel::findOrFail($designationId);

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $foundDesignation->update($payload);

            $foundDesignation->fresh();

            DB::commit();

            $resource = new DesignationResource($foundDesignation);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $designationId)
    {
        //
        $foundDesignation = DesignationModel::findOrFail($designationId);

        $foundDesignation->delete();
    }
}
