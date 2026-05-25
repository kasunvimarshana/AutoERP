<?php

declare(strict_types=1);

namespace Modules\User\Application\Events;

final readonly class UserCreated
{
    public function __construct(
        public int|string $userId,
        public ?int $tenantId,
        public string $email,
    ) {
    }
}
