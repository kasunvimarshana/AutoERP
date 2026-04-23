<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\ApprovePayrollRunServiceInterface;
use Modules\HR\Domain\Entities\PayrollRun;
use Modules\HR\Domain\Events\PayrollRunApproved;
use Modules\HR\Domain\Exceptions\PayrollRunNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\PayrollRunRepositoryInterface;
use Modules\HR\Domain\ValueObjects\PayrollRunStatus;

class ApprovePayrollRunService extends BaseService implements ApprovePayrollRunServiceInterface
{
    public function __construct(
        private readonly PayrollRunRepositoryInterface $runRepository,
    ) {
        parent::__construct($this->runRepository);
    }

    protected function handle(array $data): PayrollRun
    {
        $id = (int) ($data['id'] ?? 0);
        $userId = (int) ($data['approved_by'] ?? 0);
        $run = $this->runRepository->find($id);

        if ($run === null) {
            throw new PayrollRunNotFoundException($id);
        }

        if (! in_array($run->getStatus(), [PayrollRunStatus::DRAFT, PayrollRunStatus::PROCESSING], true)) {
            throw new DomainException('Only draft or processing payroll runs can be approved.');
        }

        $run->approve($userId);
        $saved = $this->runRepository->save($run);

        $this->addEvent(new PayrollRunApproved($saved, $run->getTenantId()));

        return $saved;
    }
}
