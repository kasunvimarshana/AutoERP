<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Customer\Application\Repositories\CustomerTaxProfileRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerTaxProfileModel;

final class EloquentCustomerTaxProfileRepository extends EloquentRepository implements CustomerTaxProfileRepositoryInterface
{
    public function __construct(CustomerTaxProfileModel $model)
    {
        parent::__construct($model);
    }
}