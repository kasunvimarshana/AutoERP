<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemBrandRequest;
use App\Http\Requests\UpdateItemBrandRequest;
use App\Http\Resources\ItemBrandCollectionResource;
use App\Http\Resources\ItemBrandResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemBrandModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class ItemBrandController extends Controller
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

        $itemBrands = ItemBrandModel::with(['parent', 'children'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new ItemBrandCollectionResource($itemBrands);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreItemBrandRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        if ($request->hasFile('image_file')) {
            $payload['image_path'] = $this->storeImage(
                $request->file('image_file'),
                (int) $tenant_id,
                'item_brands'
            );
        }

        DB::beginTransaction();

        try {
            $itemBrand = ItemBrandModel::create($payload);


            // $itemBrand->fresh();

            $itemBrand->load([
                'parent', 'children'
            ]);

            DB::commit();

            $resource = new ItemBrandResource($itemBrand);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $itemBrandId)
    {
        $itemBrand = ItemBrandModel::with(['parent', 'children'])->findOrFail($itemBrandId);

        $resource = new ItemBrandResource($itemBrand);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateItemBrandRequest $request, int $itemBrandId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundItemBrand = ItemBrandModel::with(['parent', 'children'])->findOrFail($itemBrandId);

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        $oldImagePath = $foundItemBrand->image_path;
        $newImagePath = null;

        if ($request->hasFile('image_file')) {
            $newImagePath = $this->storeImage(
                $request->file('image_file'),
                (int) $tenant_id,
                'item_brands'
            );

            $payload['image_path'] = $newImagePath;
        }

        DB::beginTransaction();

        try {
            $foundItemBrand->update($payload);

            $foundItemBrand->fresh();

            $foundItemBrand->load([
                'parent', 'children'
            ]);

            DB::commit();

            $resource = new ItemBrandResource($foundItemBrand);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $itemBrandId)
    {
        //
        $foundItemBrand = ItemBrandModel::findOrFail($itemBrandId);

        $this->deleteImageIfSafe($foundItemBrand->image_path, $foundItemBrand->tenant_id, 'item_brands');

        $foundItemBrand->delete();
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
}
