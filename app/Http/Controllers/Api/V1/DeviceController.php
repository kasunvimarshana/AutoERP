<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeviceResource;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use App\Services\AuthService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceRepositoryInterface $deviceRepository,
        private readonly AuthService $authService,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/devices",
     *     summary="List current user's devices",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $devices = $this->deviceRepository->findByUserId($request->user()->id);

        return response()->json([
            'success' => true,
            'data'    => DeviceResource::collection($devices),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/devices/{deviceId}",
     *     summary="Revoke tokens for a specific device",
     *     tags={"Devices"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function revoke(Request $request, string $deviceId): JsonResponse
    {
        $user = $request->user();

        $device = $this->deviceRepository->findByDeviceId($deviceId);

        if ($device === null || $device->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'DEVICE_NOT_FOUND', 'message' => 'Device not found.'],
            ], 404);
        }

        $this->tokenService->revokeDeviceTokens($user, $deviceId);
        $this->deviceRepository->deleteByDeviceId($deviceId);

        return response()->json([
            'success' => true,
            'message' => 'Device revoked successfully.',
        ]);
    }
}
