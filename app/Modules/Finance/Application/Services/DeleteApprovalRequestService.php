<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteApprovalRequestServiceInterface;
use Modules\Finance\Domain\Exceptions\ApprovalRequestNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalRequestRepositoryInterface;

class DeleteApprovalRequestService extends BaseService implements DeleteApprovalRequestServiceInterface
{
    public function __construct(private readonly ApprovalRequestRepositoryInterface $approvalRequestRepository)
    {
        parent::__construct($approvalRequestRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->approvalRequestRepository->find($id)) {
            throw new ApprovalRequestNotFoundException($id);
        }

        return $this->approvalRequestRepository->delete($id);
    }
}
