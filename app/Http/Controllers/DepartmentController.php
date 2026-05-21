<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentCollectionResource;
use App\Http\Resources\DepartmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DepartmentModel;

class DepartmentController extends Controller
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

        $departments = DepartmentModel::paginate($perPage, $columns, $pageName, $page);

        $resource = new DepartmentCollectionResource($departments);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $department = DepartmentModel::create($payload);

            // $department->fresh();

            DB::commit();

            $resource = new DepartmentResource($department);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $departmentId)
    {
        $departmentEntity = DepartmentModel::findOrFail($departmentId);

        $resource = new DepartmentResource($departmentEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateDepartmentRequest $request, int $departmentId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundDepartment = DepartmentModel::findOrFail($departmentId);

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $foundDepartment->update($payload);

            $foundDepartment->fresh();

            DB::commit();

            $resource = new DepartmentResource($foundDepartment);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $departmentId)
    {
        //
        $foundDepartment = DepartmentModel::findOrFail($departmentId);

        $foundDepartment->delete();
    }
}
