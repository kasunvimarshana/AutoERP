<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmploymentTypeRequest;
use App\Http\Requests\UpdateEmploymentTypeRequest;
use App\Http\Resources\EmploymentTypeCollectionResource;
use App\Http\Resources\EmploymentTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\EmploymentTypeModel;

class EmploymentTypeController extends Controller
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

        $employmentTypes = EmploymentTypeModel::paginate($perPage, $columns, $pageName, $page);

        $resource = new EmploymentTypeCollectionResource($employmentTypes);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreEmploymentTypeRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $employmentType = EmploymentTypeModel::create($payload);

            // $employmentType->fresh();

            DB::commit();

            $resource = new EmploymentTypeResource($employmentType);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $employmentTypeId)
    {
        $employmentTypeEntity = EmploymentTypeModel::findOrFail($employmentTypeId);

        $resource = new EmploymentTypeResource($employmentTypeEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateEmploymentTypeRequest $request, int $employmentTypeId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundEmploymentType = EmploymentTypeModel::findOrFail($employmentTypeId);

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $foundEmploymentType->update($payload);

            $foundEmploymentType->fresh();

            DB::commit();

            $resource = new EmploymentTypeResource($foundEmploymentType);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $employmentTypeId)
    {
        //
        $foundEmploymentType = EmploymentTypeModel::findOrFail($employmentTypeId);

        $foundEmploymentType->delete();
    }
}
