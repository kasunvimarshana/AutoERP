<?php

declare(strict_types=1);

namespace Modules\User\Services;

use App\Core\Services\BaseService;
use Modules\User\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * User Service
 * 
 * Contains business logic for User operations
 * Extends BaseService for common service layer functionality
 */
class UserService extends BaseService
{
    /**
     * UserService constructor
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new user
     *
     * @param array<string, mixed> $data
     * @return mixed
     * @throws ValidationException
     */
    public function create(array $data): mixed
    {
        if ($this->repository->emailExists($data['email'])) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        $data['password'] = Hash::make($data['password']);

        return parent::create($data);
    }

    /**
     * Update user
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return mixed
     * @throws ValidationException
     */
    public function update(int $id, array $data): mixed
    {
        if (isset($data['email']) && $this->repository->emailExists($data['email'], $id)) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return parent::update($id, $data);
    }

    /**
     * Assign role to user
     *
     * @param int $userId
     * @param string $role
     * @return mixed
     */
    public function assignRole(int $userId, string $role): mixed
    {
        $user = $this->repository->findOrFail($userId);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Revoke role from user
     *
     * @param int $userId
     * @param string $role
     * @return mixed
     */
    public function revokeRole(int $userId, string $role): mixed
    {
        $user = $this->repository->findOrFail($userId);
        $user->removeRole($role);

        return $user;
    }
}
