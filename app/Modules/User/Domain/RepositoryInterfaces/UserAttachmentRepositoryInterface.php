<?php

declare(strict_types=1);

namespace Modules\User\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\User\Domain\Entities\UserAttachment;

interface UserAttachmentRepositoryInterface extends RepositoryInterface
{
    public function findByUuid(string $uuid): ?UserAttachment;

    public function getByUser(int $userId, ?string $type = null): Collection;

    public function save(UserAttachment $attachment): UserAttachment;
}
