<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserAvatarUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UploadAvatarService extends BaseService implements UploadAvatarServiceInterface
{
    private UserRepositoryInterface $userRepository;

    public function __construct(
        UserRepositoryInterface $repository,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($repository);
        $this->userRepository = $repository;
    }

    protected function handle(array $data): User
    {
        $userId = $data['user_id'];
        $fileInfo = $data['file'];

        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $path = $this->storage->store(
            $fileInfo['tmp_path'],
            "avatars/{$userId}",
            $fileInfo['name']
        );

        $this->userRepository->updateAvatar($userId, $path);
        $user->changeAvatar($path);

        $saved = $this->userRepository->find($userId);
        $this->addEvent(new UserAvatarUpdated($saved));

        return $saved;
    }
}
