<?php

declare(strict_types=1);

namespace Modules\Settings\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Infrastructure\Http\Controllers\AuthorizedController;
use Modules\Settings\Application\Contracts\CreateSettingServiceInterface;
use Modules\Settings\Application\Contracts\DeleteSettingServiceInterface;
use Modules\Settings\Application\Contracts\FindSettingServiceInterface;
use Modules\Settings\Application\Contracts\UpdateSettingServiceInterface;
use Modules\Settings\Application\DTOs\SettingData;
use Modules\Settings\Application\DTOs\UpdateSettingData;
use Modules\Settings\Infrastructure\Http\Requests\StoreSettingRequest;
use Modules\Settings\Infrastructure\Http\Requests\UpdateSettingRequest;
use Modules\Settings\Infrastructure\Http\Resources\SettingCollection;
use Modules\Settings\Infrastructure\Http\Resources\SettingResource;

class SettingController extends AuthorizedController
{
    public function __construct(
        protected FindSettingServiceInterface $findService,
        protected CreateSettingServiceInterface $createService,
        protected UpdateSettingServiceInterface $updateService,
        protected DeleteSettingServiceInterface $deleteService,
    ) {}

    public function index(Request $request): SettingCollection
    {
        $filters = $request->only(['tenant_id', 'group_key']);

        return new SettingCollection(
            $this->findService->list($filters, $request->integer('per_page', 15), $request->integer('page', 1))
        );
    }

    public function store(StoreSettingRequest $request): JsonResponse
    {
        $v   = $request->validated();
        $dto = SettingData::fromArray([
            'tenantId'     => $v['tenant_id'],
            'groupKey'     => $v['group_key'],
            'settingKey'   => $v['setting_key'],
            'label'        => $v['label'],
            'value'        => $v['value'] ?? null,
            'defaultValue' => $v['default_value'] ?? null,
            'settingType'  => $v['setting_type'] ?? 'string',
            'description'  => $v['description'] ?? null,
            'isSystem'     => $v['is_system'] ?? false,
            'isEditable'   => $v['is_editable'] ?? true,
            'metadata'     => $v['metadata'] ?? null,
        ]);

        $setting = $this->createService->execute($dto->toArray());

        return (new SettingResource($setting))->response()->setStatusCode(201);
    }

    public function show(int $id): SettingResource|JsonResponse
    {
        $setting = $this->findService->find($id);
        if (! $setting) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new SettingResource($setting);
    }

    public function update(UpdateSettingRequest $request, int $id): SettingResource
    {
        $v   = $request->validated();
        $dto = UpdateSettingData::fromArray(array_merge(['id' => $id], $v));

        return new SettingResource($this->updateService->execute($dto->toArray()));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->deleteService->execute(['id' => $id]);

        return response()->json(['message' => 'Setting deleted successfully']);
    }
}
