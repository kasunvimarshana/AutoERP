<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Illuminate\Support\Collection;
use Modules\User\Application\Contracts\FindUserAttachmentsServiceInterface;
use Modules\User\Domain\Entities\UserAttachment;
use Modules\User\Domain\RepositoryInterfaces\UserAttachmentRepositoryInterface;

/**
 * Delegates read queries for user attachments to the repository.
 *
 * Keeping query logic here (rather than in the controller) upholds DIP:
 * controllers depend on this service interface, not on the repository directly.
 */
class FindUserAttachmentsService implements FindUserAttachmentsServiceInterface
{
    public function __construct(
        private readonly UserAttachmentRepositoryInterface $attachmentRepository
    ) {}

    public function findByUuid(string $uuid): ?UserAttachment
    {
        return $this->attachmentRepository->findByUuid($uuid);
    }

    public function getByUser(int $userId, ?string $type = null): Collection
    {
        return $this->attachmentRepository->getByUser($userId, $type);
    }
}
