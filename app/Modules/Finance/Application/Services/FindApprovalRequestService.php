<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\FindApprovalRequestServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalRequestRepositoryInterface;

class FindApprovalRequestService extends BaseService implements FindApprovalRequestServiceInterface
{
    public function __construct(private readonly ApprovalRequestRepositoryInterface $approvalRequestRepository)
    {
        parent::__construct($approvalRequestRepository);
    }

    protected function handle(array $data): mixed
    {
        return null;
    }
}
