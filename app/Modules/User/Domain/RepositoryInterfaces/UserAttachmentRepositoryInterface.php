<?php

declare(strict_types=1);

namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\UserAttachment;

interface UserAttachmentRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?UserAttachment;

    /**
     * @return iterable<int, UserAttachment>
     */
    public function getByUser(int $userId, ?string $type = null): iterable;

    public function save(UserAttachment $attachment): UserAttachment;
}
