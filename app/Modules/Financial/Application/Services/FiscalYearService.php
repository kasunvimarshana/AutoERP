<?php

declare(strict_types=1);

namespace Modules\Financial\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Financial\Application\Contracts\FiscalYearServiceInterface;
use Modules\Financial\Domain\Contracts\Repositories\FiscalYearRepositoryInterface;

class FiscalYearService extends BaseService implements FiscalYearServiceInterface
{
    public function __construct(FiscalYearRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Default execute handler — creates a fiscal year.
     */
    protected function handle(array $data): mixed
    {
        return $this->repository->create($data);
    }
}
