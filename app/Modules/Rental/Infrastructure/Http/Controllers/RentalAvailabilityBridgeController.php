<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Controllers;

use Modules\Asset\Application\Contracts\SyncAssetAvailabilityServiceInterface;
use Modules\Asset\Infrastructure\Http\Resources\AssetAvailabilityStateResource;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Rental\Infrastructure\Http\Requests\BridgeAssetAvailabilityRequest;

class RentalAvailabilityBridgeController extends AuthorizedController
{
    public function __construct(private readonly SyncAssetAvailabilityServiceInterface $syncAvailabilityService) {}

    public function reserve(BridgeAssetAvailabilityRequest $request): AssetAvailabilityStateResource
    {
        $payload = $request->validated();

        return new AssetAvailabilityStateResource($this->syncAvailabilityService->execute([
            'tenant_id' => $payload['tenant_id'],
            'org_unit_id' => $payload['org_unit_id'] ?? null,
            'asset_id' => $payload['asset_id'],
            'target_status' => 'reserved',
            'reason_code' => $payload['reason_code'] ?? 'rental_reserved',
            'source_type' => 'rental_booking',
            'source_id' => $payload['rental_booking_id'],
            'changed_by' => $request->user()?->id,
            'metadata' => $payload['metadata'] ?? null,
        ]));
    }

    public function activate(BridgeAssetAvailabilityRequest $request): AssetAvailabilityStateResource
    {
        $payload = $request->validated();

        return new AssetAvailabilityStateResource($this->syncAvailabilityService->execute([
            'tenant_id' => $payload['tenant_id'],
            'org_unit_id' => $payload['org_unit_id'] ?? null,
            'asset_id' => $payload['asset_id'],
            'target_status' => 'rented',
            'reason_code' => $payload['reason_code'] ?? 'rental_started',
            'source_type' => 'rental_booking',
            'source_id' => $payload['rental_booking_id'],
            'changed_by' => $request->user()?->id,
            'metadata' => $payload['metadata'] ?? null,
        ]));
    }

    public function release(BridgeAssetAvailabilityRequest $request): AssetAvailabilityStateResource
    {
        $payload = $request->validated();

        return new AssetAvailabilityStateResource($this->syncAvailabilityService->execute([
            'tenant_id' => $payload['tenant_id'],
            'org_unit_id' => $payload['org_unit_id'] ?? null,
            'asset_id' => $payload['asset_id'],
            'target_status' => 'available',
            'reason_code' => $payload['reason_code'] ?? 'rental_completed',
            'source_type' => 'rental_booking',
            'source_id' => $payload['rental_booking_id'],
            'changed_by' => $request->user()?->id,
            'metadata' => $payload['metadata'] ?? null,
        ]));
    }
}
