<?php

namespace Modules\User\Application\UseCases;

use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUsers
{
    /** @var array<string> Allowed filter fields */
    private const ALLOWED_FILTERS = ['tenant_id', 'first_name', 'last_name', 'email', 'active'];

    public function __construct(
        private UserRepositoryInterface $userRepo
    ) {}

    public function execute(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $repo = clone $this->userRepo;
        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repo->where($field, $value);
            }
        }
        return $repo->paginate($perPage, ['*'], 'page', $page);
    }
}
