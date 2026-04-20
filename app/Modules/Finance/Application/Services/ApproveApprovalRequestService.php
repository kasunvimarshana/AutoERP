<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\ApproveApprovalRequestServiceInterface;
use Modules\Finance\Domain\Entities\ApprovalRequest;
use Modules\Finance\Domain\Exceptions\ApprovalRequestNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalRequestRepositoryInterface;

class ApproveApprovalRequestService extends BaseService implements ApproveApprovalRequestServiceInterface
{
    public function __construct(private readonly ApprovalRequestRepositoryInterface $approvalRequestRepository)
    {
        parent::__construct($approvalRequestRepository);
    }

    protected function handle(array $data): ApprovalRequest
    {
        $id = (int) ($data['id'] ?? 0);
        $resolvedByUserId = (int) ($data['resolved_by_user_id'] ?? 0);
        $comments = isset($data['comments']) ? (string) $data['comments'] : null;

        $approvalRequest = $this->approvalRequestRepository->find($id);
        if (! $approvalRequest) {
            throw new ApprovalRequestNotFoundException($id);
        }

        if ($approvalRequest->getStatus() !== 'pending') {
            throw new DomainException('Only pending approval requests can be approved.');
        }

        $approvalRequest->approve($resolvedByUserId, $comments);

        return $this->approvalRequestRepository->save($approvalRequest);
    }
}
