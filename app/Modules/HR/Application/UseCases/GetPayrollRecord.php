<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\PayrollRecord;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class GetPayrollRecord
{
    public function __construct(private readonly PayrollRepositoryInterface $repo) {}

    public function execute(int $id): ?PayrollRecord
    {
        return $this->repo->find($id);
    }
}
