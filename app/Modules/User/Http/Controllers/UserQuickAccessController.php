<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Http\Requests\UpdateUserQuickAccessRequest;
use Modules\User\Services\UserQuickAccessService;

final class UserQuickAccessController extends AbstractUserCrudController
{
    public function __construct(
        private readonly UserQuickAccessService $service,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('current_user_record_tenant_id');
        $userId = (int) $request->attributes->get('current_user_record_id');

        $data = $this->service->get($tenantId, $userId);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function update(UpdateUserQuickAccessRequest $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('current_user_record_tenant_id');
        $userId = (int) $request->attributes->get('current_user_record_id');
        $items = (array) $request->validated('items');
        $rowVersion = $request->has('row_version') && $request->validated('row_version') !== null
            ? (int) $request->validated('row_version')
            : null;

        $result = $this->service->update($tenantId, $userId, $items, $rowVersion);
        if ($result->isFailure()) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'data' => $result->valueOrFail(),
            'message' => 'Quick Access preferences saved successfully.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $tenantId = (int) $request->attributes->get('current_user_record_tenant_id');
        $userId = (int) $request->attributes->get('current_user_record_id');

        $result = $this->service->reset($tenantId, $userId);
        if ($result->isFailure()) {
            return $this->errorResponse($result);
        }

        return response()->json([
            'data' => $result->valueOrFail(),
            'message' => 'Quick Access preferences reset to default.',
        ]);
    }
}
