<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/user/profile",
     *     summary="Get user profile",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $this->userService->getProfile($request->user()->id);

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user/profile",
     *     summary="Update user profile",
     *     tags={"User"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'min:2', 'max:255'],
            'locale'   => ['sometimes', 'string', 'max:10'],
            'timezone' => ['sometimes', 'string', 'max:50'],
        ]);

        $user = $this->userService->updateProfile($request->user()->id, $validated);

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
            'message' => 'Profile updated.',
        ]);
    }
}
