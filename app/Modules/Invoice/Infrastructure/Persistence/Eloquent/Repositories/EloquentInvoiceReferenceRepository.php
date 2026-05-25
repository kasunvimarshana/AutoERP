<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Models\InvoiceReferenceModel;

final class EloquentInvoiceReferenceRepository extends EloquentRepository implements InvoiceReferenceRepositoryInterface
{
    public function __construct(InvoiceReferenceModel $model)
    {
        parent::__construct($model);
    }
}