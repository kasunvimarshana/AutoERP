<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Finance\Application\Contracts\CancelApprovalRequestServiceInterface;
use Modules\Finance\Domain\Entities\ApprovalRequest;
use Modules\Finance\Domain\Exceptions\ApprovalRequestNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalRequestRepositoryInterface;

class CancelApprovalRequestService extends BaseService implements CancelApprovalRequestServiceInterface
{
    public function __construct(private readonly ApprovalRequestRepositoryInterface $approvalRequestRepository)
    {
        parent::__construct($approvalRequestRepository);
    }

    protected function handle(array $data): ApprovalRequest
    {
        $id = (int) ($data['id'] ?? 0);
        $comments = isset($data['comments']) ? (string) $data['comments'] : null;

        $approvalRequest = $this->approvalRequestRepository->find($id);
        if (! $approvalRequest) {
            throw new ApprovalRequestNotFoundException($id);
        }

        if (in_array($approvalRequest->getStatus(), ['approved', 'rejected', 'cancelled'], true)) {
            throw new DomainException('Approval request cannot be cancelled in its current state.');
        }

        $approvalRequest->cancel($comments);

        return $this->approvalRequestRepository->save($approvalRequest);
    }
}
