<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierTaxProfileRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierTaxProfileModel;

final class EloquentSupplierTaxProfileRepository extends EloquentRepository implements
    SupplierTaxProfileRepositoryInterface
{
    public function __construct(SupplierTaxProfileModel $model)
    {
        parent::__construct($model);
    }
}
