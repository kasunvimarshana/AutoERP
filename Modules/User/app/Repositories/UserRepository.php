<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * User Repository
 *
 * Handles data access for User model
 * Extends BaseRepository for common CRUD operations
 */
class UserRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new User;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        /** @var User|null */
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
