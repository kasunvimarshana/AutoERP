<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\UpdateApprovalRequestServiceInterface;
use Modules\Finance\Application\DTOs\ApprovalRequestData;
use Modules\Finance\Domain\Entities\ApprovalRequest;
use Modules\Finance\Domain\Exceptions\ApprovalRequestNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\ApprovalRequestRepositoryInterface;

class UpdateApprovalRequestService extends BaseService implements UpdateApprovalRequestServiceInterface
{
    public function __construct(private readonly ApprovalRequestRepositoryInterface $approvalRequestRepository)
    {
        parent::__construct($approvalRequestRepository);
    }

    protected function handle(array $data): ApprovalRequest
    {
        $dto = ApprovalRequestData::fromArray($data);
        /** @var ApprovalRequest|null $ar */
        $ar = $this->approvalRequestRepository->find((int) $dto->id);
        if (! $ar) {
            throw new ApprovalRequestNotFoundException((int) $dto->id);
        }
        if ($dto->status === 'approved' && $dto->resolved_by_user_id !== null) {
            $ar->approve($dto->resolved_by_user_id, $dto->comments);
        } elseif ($dto->status === 'rejected' && $dto->resolved_by_user_id !== null) {
            $ar->reject($dto->resolved_by_user_id, $dto->comments);
        } elseif ($dto->status === 'cancelled') {
            $ar->cancel($dto->comments);
        }

        return $this->approvalRequestRepository->save($ar);
    }
}
