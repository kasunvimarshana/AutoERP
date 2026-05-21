<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemCategoryRequest;
use App\Http\Requests\UpdateItemCategoryRequest;
use App\Http\Resources\ItemCategoryCollectionResource;
use App\Http\Resources\ItemCategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemCategoryModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class ItemCategoryController extends Controller
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

        $itemCategories = ItemCategoryModel::with(['parent', 'children'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new ItemCategoryCollectionResource($itemCategories);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreItemCategoryRequest $request)
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
                'item_categories'
            );
        }

        DB::beginTransaction();

        try {
            $itemCategory = ItemCategoryModel::create($payload);


            // $itemCategory->fresh();

            $itemCategory->load([
                'parent', 'children'
            ]);

            DB::commit();

            $resource = new ItemCategoryResource($itemCategory);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $itemCategoryId)
    {
        $itemCategory = ItemCategoryModel::with(['parent', 'children'])->findOrFail($itemCategoryId);

        $resource = new ItemCategoryResource($itemCategory);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateItemCategoryRequest $request, int $itemCategoryId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundItemCategory = ItemCategoryModel::with(['parent', 'children'])->findOrFail($itemCategoryId);

        $payload = $request->validated();
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        $oldImagePath = $foundItemCategory->image_path;
        $newImagePath = null;

        if ($request->hasFile('image_file')) {
            $newImagePath = $this->storeImage(
                $request->file('image_file'),
                (int) $tenant_id,
                'item_categories'
            );

            $payload['image_path'] = $newImagePath;
        }

        DB::beginTransaction();

        try {
            $foundItemCategory->update($payload);

            $foundItemCategory->fresh();

            $foundItemCategory->load([
                'parent', 'children'
            ]);

            DB::commit();

            $resource = new ItemCategoryResource($foundItemCategory);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $itemCategoryId)
    {
        //
        $foundItemCategory = ItemCategoryModel::findOrFail($itemCategoryId);

        $this->deleteImageIfSafe($foundItemCategory->image_path, $foundItemCategory->tenant_id, 'item_categories');

        $foundItemCategory->delete();
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
