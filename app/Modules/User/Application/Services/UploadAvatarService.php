<?php
declare(strict_types=1);
namespace Modules\User\Application\Services;

use Illuminate\Http\UploadedFile;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserAvatarUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UploadAvatarService implements UploadAvatarServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repo,
        private readonly FileStorageServiceInterface $fileStorage,
    ) {}

    public function execute(int $id, UploadedFile $file): User
    {
        $user = $this->repo->findById($id);
        if (!$user) {
            throw new UserNotFoundException($id);
        }
        $path = $this->fileStorage->storeFile($file, 'avatars');
        $this->repo->updateAvatar($id, $path);
        $updated = $this->repo->findById($id);
        event(new UserAvatarUpdated($updated->getTenantId(), $updated->getId()));
        return $updated;
    }
}
