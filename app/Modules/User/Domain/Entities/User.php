<?php
namespace Modules\User\Domain\Entities;

use Modules\Core\Domain\Entities\BaseEntity;

class User extends BaseEntity
{
    public function __construct(
        ?int $id,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $status,
        public readonly ?string $avatar = null,
        public readonly ?array $preferences = null,
        public readonly ?\DateTimeImmutable $emailVerifiedAt = null,
    ) {
        parent::__construct($id);
    }
}
