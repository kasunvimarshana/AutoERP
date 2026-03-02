<?php

declare(strict_types=1);

namespace Modules\Customization\Interfaces\Http\Controllers;

use App\Shared\Abstractions\BaseController;
use Illuminate\Http\JsonResponse;
use Modules\Customization\Application\Commands\CreateCustomFieldCommand;
use Modules\Customization\Application\Commands\DeleteCustomFieldCommand;
use Modules\Customization\Application\Commands\UpdateCustomFieldCommand;
use Modules\Customization\Application\Services\CustomFieldService;
use Modules\Customization\Interfaces\Http\Requests\CreateCustomFieldRequest;
use Modules\Customization\Interfaces\Http\Requests\UpdateCustomFieldRequest;
use Modules\Customization\Interfaces\Http\Resources\CustomFieldResource;

class CustomFieldController extends BaseController
{
    public function __construct(
        private readonly CustomFieldService $service,
    ) {}

    public function index(): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $entityType = (string) request('entity_type', '');
        $page = (int) request('page', 1);
        $perPage = (int) request('per_page', 25);

        $result = $this->service->findAllFields($tenantId, $entityType, $page, $perPage);

        return $this->success(
            data: array_map(
                fn ($f) => (new CustomFieldResource($f))->resolve(),
                $result['items']
            ),
            message: 'Custom fields retrieved successfully',
            meta: [
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'per_page' => $result['per_page'],
                'total' => $result['total'],
            ],
        );
    }

    public function store(CreateCustomFieldRequest $request): JsonResponse
    {
        try {
            $field = $this->service->createField(new CreateCustomFieldCommand(
                tenantId: $request->validated('tenant_id'),
                entityType: $request->validated('entity_type'),
                fieldKey: $request->validated('field_key'),
                fieldLabel: $request->validated('field_label'),
                fieldType: $request->validated('field_type'),
                isRequired: (bool) $request->validated('is_required', false),
                defaultValue: $request->validated('default_value'),
                sortOrder: (int) $request->validated('sort_order', 0),
                options: $request->validated('options'),
                validationRules: $request->validated('validation_rules'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new CustomFieldResource($field))->resolve(),
            message: 'Custom field created successfully',
            status: 201,
        );
    }

    public function show(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');
        $field = $this->service->findFieldById($id, $tenantId);

        if ($field === null) {
            return $this->error('Custom field not found', status: 404);
        }

        return $this->success(
            data: (new CustomFieldResource($field))->resolve(),
            message: 'Custom field retrieved successfully',
        );
    }

    public function update(UpdateCustomFieldRequest $request, int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $field = $this->service->updateField(new UpdateCustomFieldCommand(
                id: $id,
                tenantId: $tenantId,
                fieldLabel: $request->validated('field_label'),
                isRequired: (bool) $request->validated('is_required', false),
                defaultValue: $request->validated('default_value'),
                sortOrder: (int) $request->validated('sort_order', 0),
                options: $request->validated('options'),
                validationRules: $request->validated('validation_rules'),
            ));
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 422);
        }

        return $this->success(
            data: (new CustomFieldResource($field))->resolve(),
            message: 'Custom field updated successfully',
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $tenantId = (int) request('tenant_id');

        try {
            $this->service->deleteField(new DeleteCustomFieldCommand($id, $tenantId));

            return $this->success(message: 'Custom field deleted successfully');
        } catch (\DomainException $e) {
            return $this->error($e->getMessage(), status: 404);
        }
    }
}
