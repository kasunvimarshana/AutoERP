<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;

final class EloquentInvoiceRepository extends EloquentRepository implements InvoiceRepositoryInterface
{
    public function __construct(InvoiceModel $model)
    {
        parent::__construct($model);
    }
}