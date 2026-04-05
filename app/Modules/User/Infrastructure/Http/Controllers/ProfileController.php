<?php
declare(strict_types=1);
namespace Modules\User\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\GetUserServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\DTOs\ChangePasswordData;
use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Infrastructure\Http\Resources\UserResource;

class ProfileController extends Controller
{
    public function __construct(
        private readonly GetUserServiceInterface $getService,
        private readonly UpdateProfileServiceInterface $updateProfileService,
        private readonly ChangePasswordServiceInterface $changePasswordService,
        private readonly UploadAvatarServiceInterface $uploadAvatarService,
    ) {}

    public function show(int $id): JsonResponse
    {
        return response()->json(new UserResource($this->getService->findById($id)));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = UpdateProfileData::fromArray($request->all());
        $user = $this->updateProfileService->execute($id, $data);
        return response()->json(new UserResource($user));
    }

    public function changePassword(Request $request, int $id): JsonResponse
    {
        $data = ChangePasswordData::fromArray($request->all());
        $this->changePasswordService->execute($id, $data);
        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function uploadAvatar(Request $request, int $id): JsonResponse
    {
        $file = $request->file('avatar');
        $user = $this->uploadAvatarService->execute($id, $file);
        return response()->json(new UserResource($user));
    }
}
