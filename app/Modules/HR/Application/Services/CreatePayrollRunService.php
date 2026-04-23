<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\CreatePayrollRunServiceInterface;
use Modules\HR\Application\DTOs\PayrollRunData;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRunRepositoryInterface;
use Modules\HR\Domain\ValueObjects\PayrollRunStatus;

class CreatePayrollRunService extends BaseService implements CreatePayrollRunServiceInterface
{
    public function __construct(
        private readonly PayrollRunRepositoryInterface $runRepository,
    ) {
        parent::__construct($this->runRepository);
    }

    protected function handle(array $data): PayrollRun
    {
        $dto = PayrollRunData::fromArray($data);

        $existing = $this->runRepository->findByTenantAndPeriod(
            $dto->tenantId,
            $dto->periodStart,
            $dto->periodEnd,
        );

        if ($existing !== null) {
            throw new DomainException("A payroll run already exists for the period {$dto->periodStart} to {$dto->periodEnd}.");
        }

        $now = new \DateTimeImmutable;
        $run = new PayrollRun(
            tenantId: $dto->tenantId,
            periodStart: new \DateTimeImmutable($dto->periodStart),
            periodEnd: new \DateTimeImmutable($dto->periodEnd),
            status: PayrollRunStatus::DRAFT,
            processedAt: null,
            approvedAt: null,
            approvedBy: null,
            totalGross: '0.000000',
            totalDeductions: '0.000000',
            totalNet: '0.000000',
            metadata: $dto->metadata,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->runRepository->save($run);
    }
}
