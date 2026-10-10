<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Inventory\Constants\InventoryPermission;
use Modules\Inventory\Http\Resources\SellableBatchOptionResource;
use Modules\Item\Http\Resources\ItemSummaryResource;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\VehicleService\Constants\VehicleServicePermission;
use Modules\VehicleService\Http\Requests\VehicleServiceLineItemLookupRequest;
use Modules\VehicleService\Services\VehicleServiceLineItemLookupService;

final class VehicleServiceLineItemLookupController extends VehicleServiceController
{
    public function __invoke(
        VehicleServiceLineItemLookupRequest $request,
        VehicleServiceLineItemLookupService $lookup,
        PermissionCheckerInterface $permissions,
        ItemAuthorizationService $itemAuthorization,
    ): JsonResponse {
        $userId = $request->currentUserId();
        $tenantId = $request->tenantId();

        if ($userId === null
            || (! $permissions->allows($userId, $tenantId, VehicleServicePermission::JOBS_CREATE)
                && ! $permissions->allows($userId, $tenantId, VehicleServicePermission::LINES_MANAGE))) {
            throw new AuthorizationException('You do not have permission to search service job line items.');
        }

        $itemAuthorization->assert($userId, $tenantId, ItemAuthorizationService::VIEW);
        $result = $lookup->search(
            tenantId: $tenantId,
            organizationUnitId: $request->organizationUnitId(),
            search: trim((string) $request->validated('search')),
            page: $request->page(),
            perPage: $request->perPage(),
            includeBatchOptions: $permissions->allows($userId, $tenantId, InventoryPermission::STOCK_VIEW),
        );

        $data = array_map(function (array $option) use ($request): array {
            return $option['type'] === 'batch'
                ? (new SellableBatchOptionResource($option['model']))->resolve($request)
                : (new ItemSummaryResource($option['model']))->resolve($request);
        }, $result['options']);

        return response()->json([
            'data' => $data,
            'meta' => $result['meta'],
        ]);
    }
}
