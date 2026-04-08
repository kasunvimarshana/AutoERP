<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\FindPayrollServiceInterface;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRepositoryInterface;

class FindPayrollService extends BaseService implements FindPayrollServiceInterface
{
    public function __construct(private readonly PayrollRepositoryInterface $payrollRepository)
    {
        parent::__construct($payrollRepository);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\PayrollRecord>
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->payrollRepository->getByEmployee($employeeId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
