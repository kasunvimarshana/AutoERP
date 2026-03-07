<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Auth;

use App\Domain\Auth\Contracts\AuthRepositoryInterface;
use App\Infrastructure\Repositories\BaseRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

/**
 * Eloquent implementation of the Auth / User repository.
 *
 * Token issuance delegates to Passport's password grant via an internal
 * HTTP request (standard Laravel Passport flow).
 */
class UserRepository extends BaseRepository implements AuthRepositoryInterface
{
    protected array $filterable = ['status', 'tenant_id'];
    protected array $searchable = ['name', 'email'];

    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?Model
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    /**
     * {@inheritDoc}
     *
     * Issues a Passport personal access token for the authenticated user.
     *
     * @throws \App\Exceptions\AuthenticationException
     */
    public function authenticate(string $email, string $password): string
    {
        $user = $this->findByEmail($email);

        if ($user === null || !Hash::check($password, $user->password)) {
            throw new \App\Exceptions\AuthenticationException('Invalid credentials.');
        }

        if ($user->status !== 'active') {
            throw new \App\Exceptions\AuthenticationException('Account is not active.');
        }

        // Issue a personal access token via Passport.
        $token = $user->createToken('api-token')->accessToken;

        return $token;
    }

    /**
     * {@inheritDoc}
     */
    public function revokeTokens(int|string $userId): void
    {
        /** @var User $user */
        $user = $this->findOrFail($userId);

        $user->tokens()->each(function ($token): void {
            $token->revoke();
        });
    }

    public function assignRole(int|string $userId, string $role): Model
    {
        /** @var User $user */
        $user = $this->findOrFail($userId);
        $user->assignRole($role);

        return $user->fresh();
    }

    public function revokeRole(int|string $userId, string $role): Model
    {
        /** @var User $user */
        $user = $this->findOrFail($userId);
        $user->removeRole($role);

        return $user->fresh();
    }
}
