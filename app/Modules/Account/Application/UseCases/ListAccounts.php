<?php

declare(strict_types=1);

namespace Modules\Account\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class ListAccounts
{
    private const ALLOWED_FILTERS = ['tenant_id', 'code', 'name', 'type', 'subtype', 'currency', 'status', 'parent_id'];

    public function __construct(private readonly AccountRepositoryInterface $accountRepo) {}

    public function execute(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $repo = clone $this->accountRepo;

        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repo->where($field, $value);
            }
        }

        return $repo->paginate($perPage, ['*'], 'page', $page);
    }
}
