<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Audit\Services\AuditService;
use Modules\Auth\Http\Resources\UserDeviceResource;
use Modules\Auth\Models\UserDevice;
use Modules\Auth\Services\JwtTokenService;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Tenant\Services\TenantContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * UserDeviceController
 *
 * Handles device management operations
 */
class UserDeviceController extends ApiController
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuditService $auditService,
        protected JwtTokenService $tokenService
    ) {}

    /**
     * List all devices for authenticated user
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('Not authenticated');
        }

        $devices = UserDevice::where('user_id', $user->id)
            ->latest('last_used_at')
            ->get();

        return $this->success(UserDeviceResource::collection($devices));
    }

    /**
     * List all devices for a specific user (admin)
     */
    public function userDevices(string $userId): JsonResponse
    {
        $query = UserDevice::where('user_id', $userId);

        if ($this->tenantContext->hasTenant()) {
            $query->where('tenant_id', $this->tenantContext->getCurrentTenantId());
        }

        $devices = $query->latest('last_used_at')->get();

        return $this->success(UserDeviceResource::collection($devices));
    }

    /**
     * Show a specific device
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();

        $query = UserDevice::query();

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($this->tenantContext->hasTenant()) {
            $query->where('tenant_id', $this->tenantContext->getCurrentTenantId());
        }

        $device = $query->find($id);

        if (! $device) {
            return $this->notFound('Device not found');
        }

        return $this->success(new UserDeviceResource($device));
    }

    /**
     * Delete a device (logout from device)
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();

        $query = UserDevice::query();

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($this->tenantContext->hasTenant()) {
            $query->where('tenant_id', $this->tenantContext->getCurrentTenantId());
        }

        $device = $query->find($id);

        if (! $device) {
            return $this->notFound('Device not found');
        }

        try {
            TransactionHelper::execute(function () use ($device) {
                $device->delete();
            });

            $this->auditService->logEvent(
                'device.deleted',
                UserDevice::class,
                $id,
                [
                    'device_id' => $device->device_id,
                    'device_name' => $device->device_name,
                    'user_id' => $device->user_id,
                ]
            );

            return $this->success(null, 'Device removed successfully');
        } catch (\Throwable $e) {
            return $this->error(
                'Device removal failed',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete all devices except current (logout from all other devices)
     */
    public function destroyOthers(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->unauthorized('Not authenticated');
        }

        $token = request()->bearerToken();

        if (! $token) {
            return $this->error(
                'No token provided',
                Response::HTTP_BAD_REQUEST
            );
        }

        $payload = $this->tokenService->validate($token);

        if (! $payload || ! isset($payload['device_id'])) {
            return $this->error(
                'Invalid token',
                Response::HTTP_BAD_REQUEST
            );
        }

        $currentDeviceId = $payload['device_id'];

        try {
            $deletedCount = TransactionHelper::execute(function () use ($user, $currentDeviceId) {
                return UserDevice::where('user_id', $user->id)
                    ->where('device_id', '!=', $currentDeviceId)
                    ->delete();
            });

            $this->auditService->logEvent(
                'devices.deleted_others',
                UserDevice::class,
                null,
                [
                    'user_id' => $user->id,
                    'current_device_id' => $currentDeviceId,
                    'deleted_count' => $deletedCount,
                ]
            );

            return $this->success(
                ['deleted_count' => $deletedCount],
                'Other devices removed successfully'
            );
        } catch (\Throwable $e) {
            return $this->error(
                'Devices removal failed',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete all devices for user (logout from all devices)
     */
    public function destroyAll(string $userId): JsonResponse
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();

        try {
            $deletedCount = TransactionHelper::execute(function () use ($userId, $tenantId) {
                $query = UserDevice::where('user_id', $userId);

                if ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                }

                return $query->delete();
            });

            $this->auditService->logEvent(
                'devices.deleted_all',
                UserDevice::class,
                null,
                [
                    'user_id' => $userId,
                    'deleted_count' => $deletedCount,
                ]
            );

            return $this->success(
                ['deleted_count' => $deletedCount],
                'All devices removed successfully'
            );
        } catch (\Throwable $e) {
            return $this->error(
                'Devices removal failed',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
