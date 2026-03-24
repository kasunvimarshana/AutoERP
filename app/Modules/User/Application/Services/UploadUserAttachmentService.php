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
        $userId = $data['user_id'];
        $fileInfo = $data['file'];
        $type = $data['type'] ?? null;
        $metadata = $data['metadata'] ?? [];

        $user = $this->userRepository->find($userId);
        if (! $user) {
            throw new UserNotFoundException($userId);
        }

        $uuid = (string) Str::uuid();
        $path = $this->storage->store($fileInfo['tmp_path'], "users/{$userId}", $fileInfo['name']);

        $attachment = new UserAttachment(
            tenantId: $user->getTenantId(),
            userId: $userId,
            uuid: $uuid,
            name: $fileInfo['name'],
            filePath: $path,
            mimeType: $fileInfo['mime_type'],
            size: $fileInfo['size'],
            type: $type,
            metadata: $metadata
        );

        return $this->attachmentRepo->save($attachment);
    }
}
