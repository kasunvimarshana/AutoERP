<?php

namespace Modules\User\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\UserAttachment;
use Illuminate\Support\Collection;

interface UserAttachmentRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?UserAttachment;
    public function getByUser(int $userId, ?string $type = null): Collection;
    public function save(UserAttachment $attachment): UserAttachment;
}
