<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Authentication Repository
 * 
 * Handles data access for authentication operations
 */
class AuthRepository extends BaseRepository
{
    /**
     * Make model instance
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function makeModel(): \Illuminate\Database\Eloquent\Model
    {
        return new User();
    }

    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find user by reset token
     *
     * @param string $token
     * @return User|null
     */
    public function findByResetToken(string $token): ?User
    {
        return $this->model->where('reset_token', $token)->first();
    }

    /**
     * Update user password
     *
     * @param int $id
     * @param string $password
     * @return bool
     */
    public function updatePassword(int $id, string $password): bool
    {
        return $this->model->where('id', $id)->update([
            'password' => $password,
            'reset_token' => null,
            'remember_token' => Str::random(60),
        ]);
    }

    /**
     * Mark email as verified
     *
     * @param int $id
     * @return bool
     */
    public function markEmailAsVerified(int $id): bool
    {
        return $this->model->where('id', $id)->update([
            'email_verified_at' => now(),
        ]);
    }
}
