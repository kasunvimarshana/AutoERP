<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Invoice\Application\Repositories\InvoiceLineRepositoryInterface;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceLineModel;

final class EloquentInvoiceLineRepository extends EloquentRepository implements InvoiceLineRepositoryInterface
{
    public function __construct(InvoiceLineModel $model)
    {
        parent::__construct($model);
    }
}