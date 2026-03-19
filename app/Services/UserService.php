<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

final class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function getProfile(int $userId): ?User
    {
        return $this->userRepository->findById($userId);
    }

    public function updateProfile(int $userId, array $data): User
    {
        $allowed = ['name', 'locale', 'timezone'];
        $filtered = array_intersect_key($data, array_flip($allowed));
        return $this->userRepository->update($userId, $filtered);
    }
}
