<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Modules\Accounting\Models\Account;
use Modules\Core\Repositories\BaseRepository;

/**
 * Account Repository
 */
class AccountRepository extends BaseRepository
{
    protected function model(): string
    {
        return Account::class;
    }

    public function findByCode(string $code): ?Account
    {
        return $this->newQuery()->where('code', $code)->first();
    }

    public function getActiveAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->where('is_active', true)->orderBy('code')->get();
    }

    public function getRootAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()->whereNull('parent_id')->orderBy('code')->get();
    }
}
