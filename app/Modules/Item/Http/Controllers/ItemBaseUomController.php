<?php

declare(strict_types=1);

namespace Modules\Item\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Item\Http\Requests\ItemBaseUomChangeRequest;
use Modules\Item\Http\Requests\ListItemRequest;
use Modules\Item\Http\Resources\ItemBaseUomConversionPreviewResource;
use Modules\Item\Http\Resources\ItemBaseUomRevisionResource;
use Modules\Item\Http\Resources\ItemBaseUomUsageAuditResource;
use Modules\Item\Models\ItemBaseUomRevision;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemBaseUomConversionPreviewService;
use Modules\Item\Services\ItemBaseUomConversionService;
use Modules\Item\Services\ItemBaseUomUsageAuditService;
use Modules\Item\Services\ItemQueryService;

final class ItemBaseUomController
{
    public function __construct(
        private readonly ItemQueryService $items,
        private readonly ItemBaseUomUsageAuditService $usageAudit,
        private readonly ItemBaseUomConversionPreviewService $preview,
        private readonly ItemBaseUomConversionService $conversion,
        private readonly ItemAuthorizationService $authorization,
    ) {}

    public function usageAudit(ListItemRequest $request, int $item): ItemBaseUomUsageAuditResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);
        $model = $this->items->find($item, $request->tenantId(), $request->organizationUnitId());

        return new ItemBaseUomUsageAuditResource($this->usageAudit->audit($model));
    }

    public function preview(ItemBaseUomChangeRequest $request, int $item): ItemBaseUomConversionPreviewResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::CHANGE_BASE_UOM);
        $model = $this->items->find($item, $request->tenantId(), $request->organizationUnitId());

        return new ItemBaseUomConversionPreviewResource($this->preview->preview(
            $model,
            $request->newBaseUomId(),
            $request->conversionFactor(),
            $request->effectiveAt(),
        ));
    }

    public function apply(ItemBaseUomChangeRequest $request, int $item): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::CHANGE_BASE_UOM);
        $model = $this->items->item($item, $request->tenantId(), $request->organizationUnitId());
        $revision = $this->conversion->apply(
            $model,
            $request->newBaseUomId(),
            $request->conversionFactor(),
            $request->effectiveAt(),
            $request->reason(),
            $request->currentUserId(),
        );

        return (new ItemBaseUomRevisionResource($revision))
            ->response()
            ->setStatusCode(200);
    }

    public function revisions(ListItemRequest $request, int $item): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), ItemAuthorizationService::VIEW);
        $model = $this->items->item($item, $request->tenantId(), $request->organizationUnitId());
        $query = ItemBaseUomRevision::query()
            ->where('tenant_id', $model->tenant_id)
            ->where('item_id', $model->getKey());
        if ($model->organization_unit_id !== null) {
            $query->where('organization_unit_id', $model->organization_unit_id);
        }

        return ItemBaseUomRevisionResource::collection(
            $query->with(['item', 'oldBaseUom', 'newBaseUom'])
                ->latest('effective_at')
                ->latest('id')
                ->paginate($request->perPage()),
        );
    }
}
