<?php

declare(strict_types=1);

namespace Modules\User\Services;

use LogicException;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\User\Contracts\AuthenticatedUserProviderInterface;
use Modules\User\Repositories\UserRepositoryInterface;

final class AuthenticatedUserProvider implements AuthenticatedUserProviderInterface
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function currentUserRecord(): ?DataRecord
    {
        $current = $this->currentUser->current();
        if ($current === null) {
            return null;
        }

        $record = $this->users->findById($current->userId());
        if ($record === null) {
            return null;
        }

        return $record;
    }

    public function requireCurrentUserRecord(): DataRecord
    {
        $record = $this->currentUserRecord();
        if ($record === null) {
            throw new LogicException('Authenticated user record is not available.');
        }

        return $record;
    }
}
