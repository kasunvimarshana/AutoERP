<?php

declare(strict_types=1);

namespace Modules\User\Services;

use App\Core\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\User\Repositories\UserRepository;

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
     */
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create a new user
     *
     * @param  array<string, mixed>  $data
     *
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
     * @param  array<string, mixed>  $data
     *
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
     */
    public function assignRole(int $userId, string $role): mixed
    {
        $user = $this->repository->findOrFail($userId);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Revoke role from user
     */
    public function revokeRole(int $userId, string $role): mixed
    {
        $user = $this->repository->findOrFail($userId);
        $user->removeRole($role);

        return $user;
    }
}
