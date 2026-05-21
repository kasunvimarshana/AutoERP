<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemCollectionResource;
use App\Http\Resources\ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\Item\Infrastructure\Persistence\Eloquent\Models\ItemModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;

class ItemController extends Controller
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

        $items = ItemModel::with([
            'comboItems',
            'category',
            'brand',
            'baseUom',
            'purchaseUom',
            'salesUom',
            'comboItems.componentItem',
            'comboItems.componentVariant',
            'comboItems.componentUom',
            'uomConversions'
            ])->paginate($perPage, $columns, $pageName, $page);

        $resource = new ItemCollectionResource($items);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreItemRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $payload = $request->except(['combo_items', 'uom_conversions']);
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        if ($request->hasFile('image_file')) {
            $payload['image_path'] = $this->storeImage(
                $request->file('image_file'),
                (int) $tenant_id,
                'items'
            );
        }

        DB::beginTransaction();

        try {
            $item = ItemModel::create($payload);

            $comboItemsPayload = $request->input('combo_items');

            if (! empty($comboItemsPayload) && is_array($comboItemsPayload)) {
                $this->saveComboItems($item, $comboItemsPayload);
            }

            $uomConversionsPayload = $request->input('uom_conversions');

            if (! empty($uomConversionsPayload) && is_array($uomConversionsPayload)) {
                $this->saveUOMConversions($item, $uomConversionsPayload);
            }

            $item->load([
                'comboItems',
                'category',
                'brand',
                'baseUom',
                'purchaseUom',
                'salesUom',
                'comboItems.componentItem',
                'comboItems.componentVariant',
                'comboItems.componentUom',
                'uomConversions'
            ]);

            DB::commit();

            $resource = new ItemResource($item);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $itemId)
    {
        $itemEntity = ItemModel::with([
            'comboItems',
            'category',
            'brand',
            'baseUom',
            'purchaseUom',
            'salesUom',
            'comboItems.componentItem',
            'comboItems.componentVariant',
            'comboItems.componentUom',
            'uomConversions'
        ])->findOrFail($itemId);

        $resource = new ItemResource($itemEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateItemRequest $request, int $itemId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundItem = ItemModel::with(['comboItems', 'uomConversions'])->findOrFail($itemId);

        $payload = $request->except(['combo_items', 'uom_conversions']);
        $payload['tenant_id'] = $tenant_id;
        $payload['organization_unit_id'] = $organization_unit_id;

        $oldImagePath = $foundItem->image_path;
        $newImagePath = null;

        if ($request->hasFile('image_file')) {
            $newImagePath = $this->storeImage(
                $request->file('image_file'),
                (int) $tenant_id,
                'items'
            );

            $payload['image_path'] = $newImagePath;
        }

        DB::beginTransaction();

        try {
            $foundItem->update($payload);

            $comboItemsPayload = $request->input('combo_items');

            if (! empty($comboItemsPayload) && is_array($comboItemsPayload)) {
                $this->saveComboItems($foundItem, $comboItemsPayload);
            }

            $uomConversionsPayload = $request->input('uom_conversions');

            if (! empty($uomConversionsPayload) && is_array($uomConversionsPayload)) {
                $this->saveUOMConversions($foundItem, $uomConversionsPayload);
            }

            $foundItem->fresh();

            $foundItem->load([
                'comboItems',
                'category',
                'brand',
                'baseUom',
                'purchaseUom',
                'salesUom',
                'comboItems.componentItem',
                'comboItems.componentVariant',
                'comboItems.componentUom',
                'uomConversions'
            ]);

            DB::commit();

            $resource = new ItemResource($foundItem);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delete(int $itemId)
    {
        //
        $foundItem = ItemModel::findOrFail($itemId);

        $this->deleteImageIfSafe($foundItem->image_path, $foundItem->tenant_id, 'items');

        $foundItem->delete();
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

    private function saveComboItems(ItemModel $item, mixed $comboItems)
    {
        //
        $keptIds = [];
        foreach ($comboItems as $v) {
            $id = $v['id'] ?? null;
            if ($id !== null) {
                $updated = $item->comboItems()
                            ->where('tenant_id', (int) $item->tenant_id)
                            ->whereKey($id)
                            ->update($v);

                if ($updated > 0) {
                    $keptIds[] = $id;
                    continue;
                }
            }

            $created = $item->comboItems()->create([
                ...$v,
                'tenant_id' => $item->tenant_id,
                'organization_unit_id' => $item->organization_unit_id,
                'item_id' => $item->id,
            ]);
            $keptIds[] = (int) $created->id;
            $defaultLocationId = ($v['is_default']) ? $created->id : $defaultLocationId;
        }

        $cleanupQuery = $item->comboItems()->where('combo_item_id', (int) $item->id);

        if ($keptIds === []) {
            $cleanupQuery->delete();
        } else {
            $cleanupQuery->whereNotIn('id', $keptIds)->delete();
        }
    }

    public function saveUOMConversions(ItemModel $item, mixed $uomConversions)
    {
        //
        $keptIds = [];
        foreach ($uomConversions as $v) {
            $id = $v['id'] ?? null;
            if ($id !== null) {
                $updated = $item->uomConversions()
                            ->where('tenant_id', (int) $item->tenant_id)
                            ->whereKey($id)
                            ->update($v);

                if ($updated > 0) {
                    $keptIds[] = $id;
                    continue;
                }
            }

            $created = $item->uomConversions()->create([
                ...$v,
                'tenant_id' => $item->tenant_id,
                'organization_unit_id' => $item->organization_unit_id,
                'item_id' => $item->id,
            ]);
            $keptIds[] = (int) $created->id;
        }

        $cleanupQuery = $item->uomConversions()->where('item_id', (int) $item->id);

        if ($keptIds === []) {
            $cleanupQuery->delete();
        } else {
            $cleanupQuery->whereNotIn('id', $keptIds)->delete();
        }
    }
}
