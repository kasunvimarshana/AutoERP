<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseCollectionResource;
use App\Http\Resources\WarehouseResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class WarehouseController extends Controller
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

        $warehouses = WarehouseModel::with(['locations'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new WarehouseCollectionResource($warehouses);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreWarehouseRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->except(['locations']);
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        if ($request->hasFile('image_file')) {
            $payload['image_path'] = $this->storeImage(
                $request->file('image_file'),
                (int) $tenant_id,
                'warehouses'
            );
        }

        DB::beginTransaction();

        try {
            $warehouse = WarehouseModel::create($payload);

            $locationsPayload = $request->input('locations');

            $this->saveLocations($warehouse, $locationsPayload);

            // $warehouse->fresh();

            $warehouse->load([
                'locations'
            ]);

            DB::commit();

            $resource = new WarehouseResource($warehouse);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $warehouseId)
    {
        $warehouseEntity = WarehouseModel::with(['locations'])->findOrFail($warehouseId);

        $resource = new WarehouseResource($warehouseEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateWarehouseRequest $request, int $warehouseId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundWarehouse = WarehouseModel::with(['locations'])->findOrFail($warehouseId);

        $payload = $request->except(['locations']);
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        $oldImagePath = $foundWarehouse->image_path;
        $newImagePath = null;

        if ($request->hasFile('image_file')) {
            $newImagePath = $this->storeImage(
                $request->file('image_file'),
                (int) $tenant_id,
                'warehouses'
            );

            $payload['image_path'] = $newImagePath;
        }

        DB::beginTransaction();

        try {
            $foundWarehouse->update($payload);

            $locationsPayload = $request->input('locations');

            $this->saveLocations($foundWarehouse, $locationsPayload);

            $foundWarehouse->fresh();

            $foundWarehouse->load([
                'locations'
            ]);

            DB::commit();

            $resource = new WarehouseResource($foundWarehouse);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $warehouseId)
    {
        //
        $foundWarehouse = WarehouseModel::findOrFail($warehouseId);

        $this->deleteImageIfSafe($foundWarehouse->image_path, $foundWarehouse->tenant_id, 'warehouses');

        $foundWarehouse->delete();
    }

    private function storeImage(UploadedFile $image, int $tenantId, string $baseDirectory): string
    {
        return $this->fileStorageService->storeFile($image, "{$baseDirectory}/{$tenantId}");
    }

    private function deleteImageIfSafe(?string $imagePath, int $tenantId, string $baseDirectory, ?string $excludePath = null): void
    {
        if ($imagePath === null || $imagePath === '' || $imagePath === $excludePath) {
            return;
        }

        $expectedPrefix = "{$baseDirectory}/{$tenantId}/";

        if (! str_starts_with($imagePath, $expectedPrefix)) {
            return;
        }

        if ($this->fileStorageService->exists($imagePath)) {
            $this->fileStorageService->delete($imagePath);
        }
    }

    private function saveLocations(WarehouseModel $warehouse, mixed $locations)
    {
        //
        $keptIds = [];
        $defaultLocationId = null;
        foreach ($locations as $v) {
            $id = $v['id'] ?? null;
            if ($id !== null) {
                $updated = $warehouse->locations()
                            ->where('tenant_id', (int) $warehouse->tenant_id)
                            ->whereKey($id)
                            ->update($v);

                if ($updated > 0) {
                    $keptIds[] = $id;
                    $defaultLocationId = ($v['is_default']) ? $id : $defaultLocationId;
                    continue;
                }
            }

            $created = $warehouse->locations()->create([
                ...$v,
                'tenant_id' => $warehouse->tenant_id,
                'organization_unit_id' => $warehouse->organization_unit_id,
                'warehouse_id' => $warehouse->id,
            ]);
            $keptIds[] = (int) $created->id;
            $defaultLocationId = ($v['is_default']) ? $created->id : $defaultLocationId;
        }

        $cleanupQuery = $warehouse->locations()->where('tenant_id', (int) $warehouse->tenant_id);

        if ($keptIds === []) {
            $cleanupQuery->delete();
        } else {
            $cleanupQuery->whereNotIn('id', $keptIds)->delete();
        }

        $this->clearDefaultForWarehouseLocation($warehouse->id, $defaultLocationId);
    }

    private function clearDefaultForWarehouseLocation(int $warehouseId, ?int $excludeLocationId = null): void
    {
        $query = WarehouseLocationModel::where('warehouse_id', $warehouseId)->where('is_default', true);

        if ($excludeLocationId !== null) {
            $query->where('id', '!=', $excludeLocationId);
        }

        $query->update(['is_default' => false]);
    }
}
