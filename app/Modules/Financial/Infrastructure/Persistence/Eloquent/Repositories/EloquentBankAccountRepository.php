<?php

declare(strict_types=1);

namespace Modules\Financial\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Financial\Domain\Contracts\Repositories\BankAccountRepositoryInterface;
use Modules\Financial\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;

class EloquentBankAccountRepository extends EloquentRepository implements BankAccountRepositoryInterface
{
    public function __construct(BankAccountModel $model)
    {
        parent::__construct($model);
    }
}
