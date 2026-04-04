<?php
namespace Modules\User\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\Application\Contracts\ChangePasswordServiceInterface;
use Modules\User\Application\Contracts\UpdateProfileServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Application\DTOs\ChangePasswordData;
use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Infrastructure\Http\Resources\UserResource;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UpdateProfileServiceInterface $updateProfileService,
        private readonly ChangePasswordServiceInterface $changePasswordService,
        private readonly UploadAvatarServiceInterface $uploadAvatarService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $this->repository->findById((int) $request->route('id'));
        if (!$user) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new UserResource($user));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->repository->findById($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);
        $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'email'       => ['sometimes', 'email'],
            'preferences' => ['sometimes', 'array'],
        ]);
        $data = new UpdateProfileData(
            name: $request->input('name'),
            email: $request->input('email'),
            preferences: $request->input('preferences'),
        );
        $updated = $this->updateProfileService->execute($user, $data);
        return response()->json(new UserResource($updated));
    }

    public function changePassword(Request $request, int $id): JsonResponse
    {
        $user = $this->repository->findById($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8'],
        ]);
        $this->changePasswordService->execute($user, new ChangePasswordData(
            currentPassword: $request->string('current_password')->value(),
            newPassword: $request->string('new_password')->value(),
        ));
        return response()->json(['message' => 'Password changed successfully']);
    }

    public function updatePreferences(Request $request, int $id): JsonResponse
    {
        $user = $this->repository->findById($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);
        $request->validate(['preferences' => ['required', 'array']]);
        $updated = $this->updateProfileService->execute($user, new UpdateProfileData(preferences: $request->input('preferences')));
        return response()->json(new UserResource($updated));
    }

    public function uploadAvatar(Request $request, int $id): JsonResponse
    {
        $user = $this->repository->findById($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);
        $request->validate(['avatar' => ['required', 'image', 'max:2048']]);
        $updated = $this->uploadAvatarService->execute($user, $request->file('avatar'));
        return response()->json(new UserResource($updated));
    }
}
