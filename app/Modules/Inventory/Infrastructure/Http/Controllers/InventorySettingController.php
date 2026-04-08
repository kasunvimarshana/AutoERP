<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Inventory\Application\Contracts\CreateInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\FindInventorySettingServiceInterface;
use Modules\Inventory\Application\Contracts\UpdateInventorySettingServiceInterface;
use Modules\Inventory\Application\DTOs\InventorySettingData;
use Modules\Inventory\Application\DTOs\UpdateInventorySettingData;
use Modules\Inventory\Infrastructure\Http\Requests\StoreInventorySettingRequest;
use Modules\Inventory\Infrastructure\Http\Requests\UpdateInventorySettingRequest;
use Modules\Inventory\Infrastructure\Http\Resources\InventorySettingResource;

class InventorySettingController extends AuthorizedController
{
    public function __construct(
        protected FindInventorySettingServiceInterface $findService,
        protected CreateInventorySettingServiceInterface $createService,
        protected UpdateInventorySettingServiceInterface $updateService,
    ) {}

    public function show(Request $request): InventorySettingResource|JsonResponse
    {
        $tenantId = $request->integer('tenant_id');
        $setting  = $this->findService->findByTenant($tenantId);

        if (! $setting) {
            return response()->json(['message' => 'Inventory settings not found'], 404);
        }

        return new InventorySettingResource($setting);
    }

    public function store(StoreInventorySettingRequest $request): JsonResponse
    {
        $dto     = InventorySettingData::fromArray(array_merge(
            $request->validated(),
            ['tenantId' => $request->integer('tenant_id')]
        ));
        $setting = $this->createService->execute($dto->toArray());

        return (new InventorySettingResource($setting))->response()->setStatusCode(201);
    }

    public function update(UpdateInventorySettingRequest $request, int $id): InventorySettingResource
    {
        $validated       = $request->validated();
        $validated['id'] = $id;
        $dto             = UpdateInventorySettingData::fromArray($validated);
        $setting         = $this->updateService->execute($dto->toArray());

        return new InventorySettingResource($setting);
    }
}
