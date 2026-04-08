<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class GetLeaveRequest
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $repo) {}

    public function execute(int $id): ?LeaveRequest
    {
        return $this->repo->find($id);
    }
}
