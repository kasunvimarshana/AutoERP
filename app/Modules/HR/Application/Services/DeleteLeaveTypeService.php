<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteLeaveTypeServiceInterface;
use Modules\HR\Domain\Exceptions\LeaveTypeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;

class DeleteLeaveTypeService extends BaseService implements DeleteLeaveTypeServiceInterface
{
    public function __construct(
        private readonly LeaveTypeRepositoryInterface $leaveTypeRepository,
    ) {
        parent::__construct($this->leaveTypeRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $leaveType = $this->leaveTypeRepository->find($id);

        if ($leaveType === null) {
            throw new LeaveTypeNotFoundException($id);
        }

        return $this->leaveTypeRepository->delete($id);
    }
}
