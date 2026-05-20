<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSystemUserRequest;
use App\Http\Requests\UpdateSystemUserRequest;
use App\Http\Resources\SystemUserCollectionResource;
use App\Http\Resources\SystemUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Modules\SystemUser\Infrastructure\Persistence\Eloquent\Models\SystemUserModel;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class SystemUserController extends Controller
{
    //
    public function __construct(private readonly FileStorageServiceInterface $fileStorageService)
    {
        //
    }

    public function index(Request $request)
    {
        //
        $validated = $request->all();

        $pageName = config('core.pagination.page_name', 'page');
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated[$pageName] ?? 1);
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $columns = ['*'];

        $systemUsers = SystemUserModel::with(['user', 'user.roles'])->paginate($perPage, $columns, $pageName, $page);

        $resource = new SystemUserCollectionResource($systemUsers);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function store(StoreSystemUserRequest $request)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        // $payload = $request->validated();
        $payload = [
            'tenant_id' => $tenant_id,
            'organization_unit_id' => $organization_unit_id,
            'first_name' => $request->input('user.first_name'),
            'last_name' => $request->input('user.last_name'),
            'email' => $request->input('user.email'),
            'password' => $request->input('user.password'),
            'phone' => $request->input('user.phone'),
            'preferences' => $request->input('user.preferences'),
            'date_of_birth' => $request->input('user.date_of_birth'),
            'gender' => $request->input('user.gender'),
            'marital_status' => $request->input('user.marital_status'),
            'status' => $request->input('status'),
        ];

        if ($request->hasFile('user.avatar_file')) {
            $payload['avatar_path'] = $this->storeImage(
                $request->file('user.avatar_file'),
                (int) $tenant_id,
                'users'
            );
        }

        DB::beginTransaction();

        try {
            $user = UserModel::create($payload);

            $user->roles()->sync($request->input('user.roles', []));

            $systemUser = SystemUserModel::create([
                'tenant_id' => $user->tenant_id,
                'organization_unit_id' => $user->organization_unit_id,
                'code' => $request->input('code'),
                'registration_number' => $request->input('registration_number'),
                'status' => $user->status,
                'notes' => $request->input('notes'),
                'user_id' => $user->id
            ]);

            // $systemUser->fresh();

            $systemUser->load([
                'user',
                'user.roles'
            ]);

            DB::commit();

            $resource = new SystemUserResource($systemUser);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Request $request, int $systemUserId)
    {
        $systemUserEntity = SystemUserModel::with(['user', 'user.roles'])->findUserOrFail($systemUserId);

        $resource = new SystemUserResource($systemUserEntity);

        return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
    }

    public function update(UpdateSystemUserRequest $request, int $systemUserId)
    {
        $tenant_id = $request->input('tenant_id', env('DEFAULT_TENANT_ID'));
        $organization_unit_id = $request->input('organization_unit_id', env('DEFAULT_OU_ID'));

        $foundSystemUser = SystemUserModel::with(['user'])->findOrFail($systemUserId);

        // $payload = $request->validated();
        $payload = [
            'tenant_id' => $tenant_id,
            'organization_unit_id' => $organization_unit_id,
            'first_name' => $request->input('user.first_name'),
            'last_name' => $request->input('user.last_name'),
            'email' => $request->input('user.email'),
            'password' => $request->input('user.password'),
            'phone' => $request->input('user.phone'),
            'preferences' => $request->input('user.preferences'),
            'date_of_birth' => $request->input('user.date_of_birth'),
            'gender' => $request->input('user.gender'),
            'marital_status' => $request->input('user.marital_status'),
            'status' => $request->input('status'),
        ];

        $oldAvatarPath = $foundSystemUser->user->avatar_path;
        $newAvatarPath = null;

        if ($request->hasFile('user.avatar_file')) {
            $newAvatarPath = $this->storeImage(
                $request->file('user.avatar_file'),
                (int) $tenant_id,
                'users'
            );

            $payload['avatar_path'] = $newAvatarPath;
        }

        DB::beginTransaction();

        try {
            $foundSystemUser->user->update($payload);

            $foundSystemUser->user->roles()->sync($request->input('user.roles', []));

            $foundSystemUser->update([
                'tenant_id' => $foundSystemUser->user->tenant_id,
                'organization_unit_id' => $foundSystemUser->user->organization_unit_id,
                'code' => $request->input('code'),
                'registration_number' => $request->input('registration_number'),
                'status' => $foundSystemUser->user->status,
                'notes' => $request->input('notes'),
                'user_id' => $foundSystemUser->user->id
            ]);

            $foundSystemUser->fresh();

            $foundSystemUser->load([
                'user',
                'user.roles'
            ]);

            DB::commit();

            $resource = new SystemUserResource($foundSystemUser);

            return $resource->response()->setStatusCode(HttpResponse::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            if ($newAvatarPath !== null) {
                $this->deleteImageIfSafe($oldAvatarPath, (int) $payload['tenant_id'], 'users', $newAvatarPath);
            }
        }
    }

    public function delete(int $systemUserId)
    {
        //
        $foundSystemUser = SystemUserModel::with(['user'])->findOrFail($systemUserId);

        $this->deleteImageIfSafe($foundSystemUser->user->avatar_path, $foundSystemUser->tenant_id, 'users');

        $foundSystemUser->delete();
    }

    private function storeImage(UploadedFile $image, int $tenantId, string $baseDirectory): string
    {
        return $this->fileStorageService->storeFile($image, "{$baseDirectory}/{$tenantId}");
    }

    private function deleteImageIfSafe(?string $imagePath, int $tenantId, string $baseDirectory, ?string $excludePath = null): void
    {
        if ($imagePath === null || $imagePath === '' || $imagePath === $excludePath) {
            return;
        }

        $expectedPrefix = "{$baseDirectory}/{$tenantId}/";

        if (! str_starts_with($imagePath, $expectedPrefix)) {
            return;
        }

        if ($this->fileStorageService->exists($imagePath)) {
            $this->fileStorageService->delete($imagePath);
        }
    }
}
