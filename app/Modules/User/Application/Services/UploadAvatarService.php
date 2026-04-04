<?php
namespace Modules\User\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserAvatarUpdated;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UploadAvatarService implements UploadAvatarServiceInterface
{
    public function __construct(private readonly UserRepositoryInterface $repository) {}

    public function execute(User $user, UploadedFile $file): User
    {
        $path = $file->store('avatars/' . $user->tenantId, 'public');
        $updated = $this->repository->updateAvatar($user, $path);
        Event::dispatch(new UserAvatarUpdated($user->tenantId, $user->id));
        return $updated;
    }
}
