<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUOMRequest;
use App\Http\Requests\UpdateUOMRequest;
use App\Http\Resources\UOMCollectionResource;
use App\Http\Resources\UOMResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class UOMController extends Controller
{
    //
    public function __construct(private readonly FileStorageServiceInterface $fileStorageService)
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

        $uoms = UnitOfMeasureModel::paginate($perPage, $columns, $pageName, $page);

        $resource = new UOMCollectionResource($uoms);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreUOMRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->except(['locations']);
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $uom = UnitOfMeasureModel::create($payload);

            // $uom->fresh();

            DB::commit();

            $resource = new UOMResource($uom);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $uomId)
    {
        $uomEntity = UnitOfMeasureModel::findOrFail($uomId);

        $resource = new UOMResource($uomEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateUOMRequest $request, int $uomId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundUOM = UnitOfMeasureModel::findOrFail($uomId);

        $payload = $request->except(['locations']);
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        DB::beginTransaction();

        try {
            $foundUOM->update($payload);

            $foundUOM->fresh();

            DB::commit();

            $resource = new UOMResource($foundUOM);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $uomId)
    {
        //
        $foundUOM = UnitOfMeasureModel::findOrFail($uomId);

        $foundUOM->delete();
    }
}
