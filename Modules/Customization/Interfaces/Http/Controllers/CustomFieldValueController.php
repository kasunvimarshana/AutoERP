<?php

declare(strict_types=1);

namespace Modules\Customization\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Customization\Application\Commands\SetCustomFieldValuesCommand;
use Modules\Customization\Application\Services\CustomFieldValueService;
use Modules\Customization\Interfaces\Http\Requests\SetCustomFieldValuesRequest;
use Modules\Customization\Interfaces\Http\Resources\CustomFieldValueResource;

class CustomFieldValueController extends BaseController
{
    public function __construct(
        private readonly CustomFieldValueService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $entityType = (string) request('entity_type', '');
        $entityId = (int) request('entity_id', 0);

        $values = $this->service->findValuesForEntity($tenantId, $entityType, $entityId);

        return $this->success(
            data: array_map(
                fn ($v) => (new CustomFieldValueResource($v))->resolve(),
                $values
            ),
            message: 'Custom field values retrieved successfully',
        );
    }

    public function store(SetCustomFieldValuesRequest $request): JsonResponse
    {
        try {
            $values = $this->service->setValues(new SetCustomFieldValuesCommand(
                tenantId: $request->validated('tenant_id'),
                entityType: $request->validated('entity_type'),
                entityId: $request->validated('entity_id'),
                values: $request->validated('values'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: array_map(
                fn ($v) => (new CustomFieldValueResource($v))->resolve(),
                $values
            ),
            message: 'Custom field values set successfully',
        );
    }
}
