<?php

declare(strict_types=1);

namespace Modules\User\Application\UseCases;

use Modules\User\Domain\Entities\User;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

class GetUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function execute(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function findByEmail(int $tenantId, string $email): ?User
    {
        return $this->userRepository->findByEmail($tenantId, $email);
    }
}
