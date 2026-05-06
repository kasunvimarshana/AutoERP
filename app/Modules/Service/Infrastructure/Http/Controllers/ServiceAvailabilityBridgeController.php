<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Controllers;

use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Asset\Infrastructure\Http\Resources\AssetAvailabilityStateResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Service\Infrastructure\Http\Requests\BridgeAssetAvailabilityRequest;

class ServiceAvailabilityBridgeController extends AuthorizedController
{
    public function __construct(private readonly SyncAssetAvailabilityServiceInterface $syncAvailabilityService) {}

    public function startDowntime(BridgeAssetAvailabilityRequest $request): AssetAvailabilityStateResource
    {
        $payload = $request->validated();

        return new AssetAvailabilityStateResource($this->syncAvailabilityService->execute([
            'tenant_id' => $payload['tenant_id'],
            'org_unit_id' => $payload['org_unit_id'] ?? null,
            'asset_id' => $payload['asset_id'],
            'target_status' => 'in_service',
            'reason_code' => $payload['reason_code'] ?? 'service_started',
            'source_type' => 'service_work_order',
            'source_id' => $payload['service_work_order_id'],
            'changed_by' => $request->user()?->id,
            'metadata' => $payload['metadata'] ?? null,
        ]));
    }

    public function endDowntime(BridgeAssetAvailabilityRequest $request): AssetAvailabilityStateResource
    {
        $payload = $request->validated();

        return new AssetAvailabilityStateResource($this->syncAvailabilityService->execute([
            'tenant_id' => $payload['tenant_id'],
            'org_unit_id' => $payload['org_unit_id'] ?? null,
            'asset_id' => $payload['asset_id'],
            'target_status' => 'available',
            'reason_code' => $payload['reason_code'] ?? 'service_completed',
            'source_type' => 'service_work_order',
            'source_id' => $payload['service_work_order_id'],
            'changed_by' => $request->user()?->id,
            'metadata' => $payload['metadata'] ?? null,
        ]));
    }
}
