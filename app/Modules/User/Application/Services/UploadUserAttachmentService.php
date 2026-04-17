<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Illuminate\Support\Str;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\UploadUserAttachmentServiceInterface;
use Modules\User\Domain\Entities\UserAttachment;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class UploadUserAttachmentService extends BaseService implements UploadUserAttachmentServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        protected UserAttachmentRepositoryInterface $attachmentRepo,
        protected FileStorageServiceInterface $storage
    ) {
        parent::__construct($userRepository);
    }

    protected function handle(array $data): UserAttachment
    {
        $userId = (int) $data['user_id'];
        /** @var array<string, mixed> $fileInfo */
        $fileInfo = is_array($data['file'] ?? null) ? $data['file'] : [];
        $type = isset($data['type']) && is_string($data['type']) ? $data['type'] : null;
        $metadata = isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null;

        $tmpPath = isset($fileInfo['tmp_path']) && is_string($fileInfo['tmp_path']) ? $fileInfo['tmp_path'] : null;
        $name = isset($fileInfo['name']) && is_string($fileInfo['name']) ? $fileInfo['name'] : null;
        $mimeType = isset($fileInfo['mime_type']) && is_string($fileInfo['mime_type']) ? $fileInfo['mime_type'] : null;
        $size = isset($fileInfo['size']) ? (int) $fileInfo['size'] : null;

        if (
            $tmpPath === null || $tmpPath === ''
            || $name === null || $name === ''
            || $mimeType === null || $mimeType === ''
            || $size === null || $size < 0
        ) {
            throw new \InvalidArgumentException('Invalid attachment file payload.');
        }

        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $storedPath = null;

        try {
            $uuid = (string) Str::uuid();
            $storedPath = $this->storage->store($tmpPath, "users/{$userId}", $name);

            $attachment = new UserAttachment(
                tenantId: $user->getTenantId(),
                userId: $userId,
                uuid: $uuid,
                name: $name,
                filePath: $storedPath,
                mimeType: $mimeType,
                size: $size,
                type: $type,
                metadata: $metadata
            );

            return $this->attachmentRepo->save($attachment);
        } catch (\Throwable $exception) {
            if (is_string($storedPath) && $storedPath !== '') {
                $this->storage->delete($storedPath);
            }

            throw $exception;
        }
    }
}
