<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\UploadAvatarServiceInterface;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Events\UserAvatarUpdated;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UploadAvatarService extends BaseService implements UploadAvatarServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly FileStorageServiceInterface $storage
    ) {
        parent::__construct($userRepository);
    }

    protected function handle(array $data): User
    {
        $userId = (int) $data['user_id'];
        /** @var array<string, mixed> $fileInfo */
        $fileInfo = is_array($data['file'] ?? null) ? $data['file'] : [];
        $tmpPath = isset($fileInfo['tmp_path']) && is_string($fileInfo['tmp_path']) ? $fileInfo['tmp_path'] : null;
        $originalName = isset($fileInfo['name']) && is_string($fileInfo['name']) ? $fileInfo['name'] : null;

        if ($tmpPath === null || $tmpPath === '' || $originalName === null || $originalName === '') {
            throw new \InvalidArgumentException('Invalid avatar file payload.');
        }

        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $previousAvatarPath = $user->getAvatar();
        $storedAvatarPath = null;

        try {
            $storedAvatarPath = $this->storage->store(
                $tmpPath,
                "avatars/{$userId}",
                $originalName
            );

            $this->userRepository->updateAvatar($userId, $storedAvatarPath);
            $user->changeAvatar($storedAvatarPath);

            if (
                $previousAvatarPath !== null
                && $previousAvatarPath !== ''
                && $previousAvatarPath !== $storedAvatarPath
            ) {
                DB::afterCommit(fn (): bool => $this->storage->delete($previousAvatarPath));
            }

            $saved = $this->userRepository->find($userId);
            $this->addEvent(new UserAvatarUpdated($saved));

            return $saved;
        } catch (\Throwable $exception) {
            if (is_string($storedAvatarPath) && $storedAvatarPath !== '') {
                $this->storage->delete($storedAvatarPath);
            }

            throw $exception;
        }
    }
}
